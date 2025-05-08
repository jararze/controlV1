<?php

namespace App\Jobs;

use App\Imports\ExcesoImport;
use App\Traits\LockFileProcessingTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ProcessExcesoFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LockFileProcessingTrait;

    public $tries = 3;
    public $timeout = 3600; // 1 hora
    protected $filePath;
    protected $batchId;
    protected $fechaHora;
    protected $fileName;

    /**
     * Create a new job instance.
     */
    public function __construct($filePath, $batchId, $fechaHora, $fileName)
    {
        $this->filePath = $filePath;
        $this->batchId = $batchId;
        $this->fechaHora = $fechaHora;
        $this->fileName = $fileName;
    }

    /**
     * Execute the job.
     */
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Aumentar límites
        ini_set('memory_limit', '2048M'); // Aumentado a 2GB
        set_time_limit(3600);
        DB::statement('SET SESSION wait_timeout = 28800');

        try {
            // Crear archivo de bloqueo
            $this->createLockFile('exceso', 0);

            // Fix path construction using storage_path helper with proper directory separators
            // Ensure consistent path separators by using DIRECTORY_SEPARATOR
            $absolutePath = storage_path('app' . DIRECTORY_SEPARATOR . $this->filePath);

            // Alternative approach if the above doesn't work
            // $disk = Storage::disk('public');
            // $absolutePath = $disk->path($this->filePath);

            Log::info('Verificando ruta del archivo', [
                'ruta_relativa' => $this->filePath,
                'ruta_absoluta' => $absolutePath,
                'existe' => file_exists($absolutePath) ? 'Sí' : 'No'
            ]);

            if (!file_exists($absolutePath)) {
                Log::error('File not found: ' . $absolutePath);

                // Try an alternative path as fallback
                $alternativePath = public_path('storage' . DIRECTORY_SEPARATOR . str_replace('public/', '', $this->filePath));

                Log::info('Intentando ruta alternativa', [
                    'ruta_alternativa' => $alternativePath,
                    'existe' => file_exists($alternativePath) ? 'Sí' : 'No'
                ]);

                if (file_exists($alternativePath)) {
                    $absolutePath = $alternativePath;
                } else {
                    throw new \Exception('File not found at: ' . $absolutePath);
                }
            }

            // The rest of your code remains the same...
            // Determinar si es un archivo grande
            $fileSize = filesize($absolutePath);
            $isLargeFile = $fileSize > 5 * 1024 * 1024; // Si es mayor a 5MB
            $extension = strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));

            // Si es un CSV grande, usamos procesamiento manual optimizado
            if ($isLargeFile && $extension === 'csv') {
                Log::info('Detectado archivo grande, usando procesamiento optimizado', [
                    'file' => $this->fileName,
                    'size' => $fileSize
                ]);
                $this->processLargeCSVFile($absolutePath);
            } else {
                // Para archivos Excel o CSV pequeños, usamos el importador existente
                // We need to ensure we're passing the proper path here
                Excel::import(
                    new ExcesoImport($this->fileName, $this->batchId, $this->fechaHora),
                    $absolutePath,
                    null  // No disk specified, use raw file path
                );
            }

            // Eliminar archivo de bloqueo
            $this->removeLockFile('exceso');

            Log::info('Archivo Exceso procesado correctamente', [
                'file' => $this->fileName,
                'batch_id' => $this->batchId
            ]);
        } catch (\Exception $e) {
            $this->removeLockFile('exceso');

            Log::error('Error procesando archivo Exceso', [
                'file' => $this->fileName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Procesa un archivo CSV grande usando procesamiento por lotes optimizado
     *
     * @param string $filePath Ruta absoluta al archivo CSV
     * @return void
     */
    protected function processLargeCSVFile($filePath)
    {
        // Crear un archivo temporal más limpio
        $cleanedFile = $this->createCleanedCsvFile($filePath);
        $processFilePath = $cleanedFile ?: $filePath;

        try {
            // Analizar el archivo para detectar su estructura
            $fileStructure = $this->analyzeCSVStructure($processFilePath);
            $maxColumns = $fileStructure['max_columns'];
            $delimiter = $fileStructure['delimiter'];

            // Leer el archivo línea por línea para mayor control
            $handle = fopen($processFilePath, 'r');

            // Leer encabezados y normalizarlos
            $headers = fgetcsv($handle, 0, $delimiter);
            $headers = $this->normalizeHeaders($headers);

            // Determinar el total de líneas (excluyendo el encabezado ya leído)
            $totalRecords = $fileStructure['total_lines'] - 1;
            $this->createLockFile('exceso', $totalRecords);

            Log::info('Iniciando procesamiento de CSV grande', [
                'file' => $this->fileName,
                'total_records' => $totalRecords,
                'detected_columns' => count($headers),
                'max_columns' => $maxColumns,
                'detected_delimiter' => $delimiter
            ]);

            // Procesar el archivo en lotes
            $processedRecords = 0;
            $batchRecords = [];
            $batchSize = 100; // Tamaño del lote
            $processedKeys = [];

            // Procesar cada línea del archivo
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                // Asegurar que la fila tenga la misma cantidad de columnas que los encabezados
                $rowSize = count($row);

                if ($rowSize < count($headers)) {
                    // Si la fila tiene menos columnas, rellenar con valores vacíos
                    $row = array_pad($row, count($headers), '');
                } elseif ($rowSize > count($headers)) {
                    // Si la fila tiene más columnas, truncar
                    $row = array_slice($row, 0, count($headers));
                }

                // Combinar encabezados con valores
                $record = array_combine($headers, $row);

                // Procesar registro
                $processedRecord = $this->processRecord($record, $processedKeys);

                if ($processedRecord) {
                    $batchRecords[] = $processedRecord;
                }

                // Procesar lote si alcanzamos el tamaño del lote
                if (count($batchRecords) >= $batchSize) {
                    $processedInBatch = $this->processBatch($batchRecords);
                    $processedRecords += $processedInBatch;
                    $this->updateLockFileProgress('exceso', $processedRecords);
                    $batchRecords = []; // Reiniciar para el siguiente lote

                    // Liberar memoria
                    gc_collect_cycles();
                }
            }

            // Procesar el último lote si queda alguno
            if (!empty($batchRecords)) {
                $processedInBatch = $this->processBatch($batchRecords);
                $processedRecords += $processedInBatch;
                $this->updateLockFileProgress('exceso', $processedRecords);
            }

            // Cerrar el archivo
            fclose($handle);

            // Eliminar archivo temporal si existe
            if ($cleanedFile && file_exists($cleanedFile)) {
                unlink($cleanedFile);
            }

            Log::info('Procesamiento de CSV grande completado', [
                'file' => $this->fileName,
                'total_processed' => $processedRecords
            ]);

        } catch (\Exception $e) {
            // Limpiar archivos temporales en caso de error
            if ($cleanedFile && file_exists($cleanedFile)) {
                unlink($cleanedFile);
            }

            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }

            Log::error('Error procesando CSV: ' . $e->getMessage(), [
                'file' => $this->fileName,
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Crea un archivo CSV limpio a partir del original para evitar problemas de formato
     */
    private function createCleanedCsvFile($filePath)
    {
        try {
            // Analizar la estructura del archivo para detectar el delimitador
            $structure = $this->analyzeCSVStructure($filePath);
            $delimiter = $structure['delimiter'];

            // Abrir el archivo original
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                return null;
            }

            // Crear archivo temporal
            $tempFile = tempnam(sys_get_temp_dir(), 'csv_clean');
            $tempHandle = fopen($tempFile, 'w');

            // Leer y limpiar cada línea
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                // Eliminar espacios en blanco y caracteres nulos
                $cleanRow = array_map(function($cell) {
                    // Convertir valores null a string vacío
                    if ($cell === null) return '';

                    // Eliminar caracteres de control y espacios al inicio/fin
                    $clean = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $cell));
                    return $clean;
                }, $row);

                // Escribir la fila limpia
                fputcsv($tempHandle, $cleanRow, ','); // Usamos coma como delimitador estándar
            }

            // Cerrar los archivos
            fclose($handle);
            fclose($tempHandle);

            return $tempFile;

        } catch (\Exception $e) {
            Log::warning('Error al crear CSV limpio: ' . $e->getMessage());

            // Cerrar los manejadores si están abiertos
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            if (isset($tempHandle) && is_resource($tempHandle)) {
                fclose($tempHandle);
            }

            return null;
        }
    }

    /**
     * Analiza la estructura del archivo CSV para determinar formato y delimitador
     */
    private function analyzeCSVStructure($filePath)
    {
        $result = [
            'total_lines' => 0,
            'max_columns' => 0,
            'delimiter' => ','
        ];

        // Detectar el delimitador probando diferentes opciones
        $possibleDelimiters = [',', ';', "\t", '|'];
        $delimiterCounts = [];

        $sample = $this->readFileSample($filePath, 5); // Leer las primeras 5 líneas

        foreach ($possibleDelimiters as $delimiter) {
            $delimiterCounts[$delimiter] = 0;

            foreach ($sample as $line) {
                $columns = str_getcsv($line, $delimiter);
                $delimiterCounts[$delimiter] += count($columns);
            }
        }

        // El delimitador con mayor número de columnas es probablemente el correcto
        arsort($delimiterCounts);
        $result['delimiter'] = key($delimiterCounts);

        // Contar líneas y determinar máximo de columnas
        $handle = fopen($filePath, 'r');
        $lineCount = 0;
        $maxColumns = 0;

        while (($line = fgets($handle)) !== false) {
            $lineCount++;
            $columns = str_getcsv($line, $result['delimiter']);
            $maxColumns = max($maxColumns, count($columns));
        }

        fclose($handle);

        $result['total_lines'] = $lineCount;
        $result['max_columns'] = $maxColumns;

        return $result;
    }

    /**
     * Lee una muestra de líneas del archivo
     */
    private function readFileSample($filePath, $lines = 5)
    {
        $sample = [];
        $handle = fopen($filePath, 'r');

        for ($i = 0; $i < $lines; $i++) {
            $line = fgets($handle);
            if ($line === false) break;
            $sample[] = $line;
        }

        fclose($handle);
        return $sample;
    }

    /**
     * Normaliza los encabezados para hacerlos consistentes
     */
    private function normalizeHeaders($headers)
    {
        if (!is_array($headers)) {
            return [];
        }

        $normalized = [];
        $counts = [];

        foreach ($headers as $header) {
            // Limpiar y normalizar
            $clean = trim(strtolower($header));
            $clean = preg_replace('/[^a-z0-9_]+/', '_', $clean);
            $clean = rtrim($clean, '_');

            // Si está vacío, usar un nombre genérico
            if (empty($clean)) {
                $clean = 'column';
            }

            // Manejar duplicados
            if (in_array($clean, $normalized)) {
                $counts[$clean] = isset($counts[$clean]) ? $counts[$clean] + 1 : 1;
                $normalized[] = $clean . '_' . $counts[$clean];
            } else {
                $normalized[] = $clean;
            }
        }

        return $normalized;
    }

    /**
     * Procesa un registro individual
     */
    private function processRecord($record, &$processedKeys)
    {
        // Validación básica
        if (empty($record['placa'])) {
            return null;
        }

        // Crear una clave única para evitar duplicados
        $placa = trim($record['placa']);
        $fecha = isset($record['fecha_exceso']) ? trim($record['fecha_exceso']) : '';
        $uniqueKey = $placa . '_' . $fecha;

        // Evitar duplicados en el mismo batch
        if (isset($processedKeys[$uniqueKey])) {
            return null;
        }
        $processedKeys[$uniqueKey] = true;

        // Procesamiento de fechas
        $fechaExceso = null;
        if (!empty($record['fecha_exceso'])) {
            if (is_numeric($record['fecha_exceso'])) {
                $fechaExceso = $this->transformExcelDate($record['fecha_exceso']);
            } else {
                try {
                    $fechaExceso = $this->transformDate($record['fecha_exceso']);
                } catch (\Exception $e) {
                    Log::warning("Error al parsear fecha exceso: " . $e->getMessage());
                }
            }
        }

        $fechaRestitucion = null;
        if (!empty($record['fecha_restitucion'])) {
            if (is_numeric($record['fecha_restitucion'])) {
                $fechaRestitucion = $this->transformExcelDate($record['fecha_restitucion']);
            } else {
                try {
                    $fechaRestitucion = $this->transformDate($record['fecha_restitucion']);
                } catch (\Exception $e) {
                    Log::warning("Error al parsear fecha restitucion: " . $e->getMessage());
                }
            }
        }

        // Preparar datos para inserción
        return [
            'PLACA' => $placa,
            'GRUPO' => $this->nullIfEmpty($record['grupo'] ?? null),
            'DESCRIPCION' => $this->nullIfEmpty($record['descripcion'] ?? null),
            'FECHA_EXCESO' => $fechaExceso,
            'FECHA_RESTITUCION' => $fechaRestitucion,
            'UBICACION' => $this->nullIfEmpty($record['ubicacion'] ?? null),
            'DIRECCION' => $this->nullIfEmpty($record['direccion'] ?? null),
            'DURACION_SEG' => is_numeric($record['duracion_seg'] ?? null) ? $record['duracion_seg'] : null,
            'VELOCIDAD_MAXIMA' => is_numeric($record['velocidad_maxima'] ?? null) ? $record['velocidad_maxima'] : null,
            'batch_id' => $this->batchId,
            'file_name' => $this->fileName,
            'fecha_registro' => $this->fechaHora,
            'final_status' => "1"
        ];
    }

    /**
     * Procesa un lote de registros
     */
    private function processBatch($records)
    {
        if (empty($records)) {
            return 0;
        }

        $processed = 0;

        // Utilizar transacción para cada lote
        DB::beginTransaction();

        try {
            foreach ($records as $record) {
                // Crear el modelo y guardarlo
                \App\Models\Exceso::create($record);
                $processed++;
            }

            // Confirmar transacción
            DB::commit();

        } catch (\Exception $e) {
            // Revertir en caso de error
            DB::rollBack();

            Log::error('Error procesando lote: ' . $e->getMessage(), [
                'records' => count($records),
                'exception' => $e->getMessage()
            ]);
        }

        return $processed;
    }

    /**
     * Transforma una fecha en varios formatos posibles a Y-m-d
     */
    private function transformDate($value)
    {
        if (empty($value)) {
            return null;
        }

        // Limpia el valor
        $value = trim($value);
        if (empty($value)) {
            return null;
        }

        try {
            // Intentar formato d/m/y
            if (preg_match('/\d{1,2}\/\d{1,2}\/\d{2}$/', $value)) {
                return \Carbon\Carbon::createFromFormat('d/m/y', $value)->format('Y-m-d');
            }

            // Intentar formato d/m/Y
            if (preg_match('/\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
                return \Carbon\Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
            }

            // Intentar formato Y-m-d
            if (preg_match('/\d{4}-\d{1,2}-\d{1,2}$/', $value)) {
                return \Carbon\Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
            }

            // Si no se pudo convertir, intentar con parse genérico
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning("Error transformando fecha: " . $value . " - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Transforma una fecha de Excel a Y-m-d
     */
    private function transformExcelDate($value)
    {
        if (empty($value) || !is_numeric($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning("Error transformando fecha Excel: " . $value . " - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Devuelve null si el valor está vacío
     */
    private function nullIfEmpty($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        return trim($value) ?: null;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->removeLockFile('exceso');

        Log::error('Job ProcessExcesoFile falló', [
            'file' => $this->fileName,
            'error' => $exception->getMessage()
        ]);
    }
}
