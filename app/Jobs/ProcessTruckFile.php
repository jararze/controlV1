<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\TruckPlanImport;
use Illuminate\Bus\Queueable;
use App\Traits\LockFileProcessingTrait;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Models\Truck;
use App\Models\TruckHistory;
use Carbon\Carbon;

class ProcessTruckFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LockFileProcessingTrait;

    public $tries = 3;  // Allow retries
    public $timeout = 7200;  // 2 horas de timeout
    public $maxExceptions = 3;

    // Usar un valor más bajo para evitar problemas de memoria y timeouts de MySQL
    protected $batchSize = 100;

    protected $filePath;
    protected $fileName;
    protected $batchId;
    protected $fechaHora;

    // Mapeo de columnas conocidas para ayudar a identificar datos en CSV irregulares
    protected $columnMappings = [
        'planilla' => ['planilla', 'nro_planilla', 'numero_planilla'],
        'patente' => ['patente', 'patente_camion', 'pat'],
        'cod_ori' => ['cod_ori', 'codigo_origen', 'cod_origen'],
        'deposito_origen' => ['deposito_origen', 'origen', 'dep_origen'],
        'cod_des' => ['cod_des', 'codigo_destino', 'cod_destino'],
        'deposito_destino' => ['deposito_destino', 'destino', 'dep_destino'],
        'fecha_salida' => ['fecha_salida', 'fecha_sal', 'salida_fecha'],
        'fecha_entrada' => ['fecha_entrada', 'fecha_ent', 'entrada_fecha', 'fecha_llegada'],
        'hora_salida' => ['hora_salida', 'hora_sal', 'salida_hora'],
        'hora_entrada' => ['hora_entrada', 'hora_ent', 'entrada_hora', 'hora_llegada'],
        'cod_prod' => ['cod_prod', 'codigo_producto', 'cod_producto'],
        'producto' => ['producto', 'nombre_producto', 'descripcion_producto'],
    ];

    /**
     * Create a new job instance.
     */
    public function __construct($filePath, $batchId, $fechaHora, $fileName)
    {
        $this->filePath = $filePath;
        $this->batchId = (string) $batchId;
        $this->fechaHora = $fechaHora;
        $this->fileName = $fileName;
    }

    /**
     * Execute the job.
     * @throws \Exception
     */
    public function handle(): void
    {
        // Aumentar memoria disponible
        ini_set('memory_limit', '1024M');
        // Aumentar tiempo máximo de ejecución del script
        set_time_limit(7200);
        // Aumentar tiempo máximo de espera de mysql
        DB::statement('SET SESSION wait_timeout = 28800');
        DB::statement('SET SESSION interactive_timeout = 28800');

        try {
            $absolutePath = storage_path('app/' . $this->filePath);

            Log::info('Verificando ruta del archivo', [
                'ruta_relativa' => $this->filePath,
                'ruta_absoluta' => $absolutePath,
                'existe' => file_exists($absolutePath) ? 'Sí' : 'No'
            ]);

            if (!file_exists($absolutePath)) {
                Log::error('File not found: ' . $absolutePath);
                throw new \Exception('File not found at: ' . $absolutePath);
            }

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
                $this->processWithExcelImport($absolutePath);
            }

            Log::info('File processed successfully', [
                'batch_id' => $this->batchId,
                'file' => $this->fileName
            ]);

            // Eliminar el archivo de bloqueo al finalizar correctamente
            $this->removeLockFile('truck');

        } catch (\Exception $e) {
            // Asegurarse de eliminar el archivo de bloqueo en caso de error
            $this->removeLockFile('truck');

            Log::error('Error detallado en ProcessTruckFile: ' . $e->getMessage(), [
                'file' => $this->fileName,
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Procesa un archivo CSV grande usando procesamiento por lotes optimizado con manejo robusto de formatos irregulares
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
            $this->createLockFile('truck', $totalRecords);

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
            $batchSize = $this->batchSize;
            $processedRegistries = [];

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

                // Procesar registro utilizando mapeo flexible de columnas
                $processedRecord = $this->processRecord($record, $processedRegistries);

                if ($processedRecord) {
                    $batchRecords[] = $processedRecord;
                }

                // Procesar lote si alcanzamos el tamaño del lote
                if (count($batchRecords) >= $batchSize) {
                    $processedInBatch = $this->processBatch($batchRecords);
                    $processedRecords += $processedInBatch;
                    $this->updateLockFileProgress('truck', $processedRecords);
                    $batchRecords = []; // Reiniciar para el siguiente lote

                    // Liberar memoria
                    gc_collect_cycles();
                }
            }

            // Procesar el último lote si queda alguno
            if (!empty($batchRecords)) {
                $processedInBatch = $this->processBatch($batchRecords);
                $processedRecords += $processedInBatch;
                $this->updateLockFileProgress('truck', $processedRecords);
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
     * Procesa un registro individual utilizando mapeo flexible de columnas
     */
    private function processRecord($record, &$processedRegistries)
    {
        // Obtener valores clave usando mapeo flexible
        $planilla = $this->getValueFromRecord($record, 'planilla');
        $patente = $this->getValueFromRecord($record, 'patente');

        // Validación básica
        if (empty($planilla) || empty($patente)) {
            return null;
        }

        // Limpiar patente
        $patente = $this->cleanPatente($patente);
        if (empty($patente)) {
            return null;
        }

        // Clave única para evitar duplicados
        $codProducto = $this->getValueFromRecord($record, 'cod_prod');
        $uniqueKey = $planilla . '_' . $patente . '_' . $codProducto;

        // Omitir si ya fue procesado
        if (isset($processedRegistries[$uniqueKey])) {
            return null;
        }

        // Marcar como procesado
        $processedRegistries[$uniqueKey] = true;

        // Transformar fechas
        $fechaSalida = $this->transformDate($this->getValueFromRecord($record, 'fecha_salida'));
        $fechaLlegada = $this->transformDate($this->getValueFromRecord($record, 'fecha_entrada'));
        $fechaOrden = $this->transformDate($this->getValueFromRecord($record, 'fecha_orden'));

        // Preparar datos para inserción/actualización
        return [
            'cod' => $this->nullIfEmpty($this->getValueFromRecord($record, 'cod_ori')),
            'deposito_origen' => $this->nullIfEmpty($this->getValueFromRecord($record, 'deposito_origen')),
            'cod_destino' => $this->nullIfEmpty($this->getValueFromRecord($record, 'cod_des')),
            'deposito_destino' => $this->nullIfEmpty($this->getValueFromRecord($record, 'deposito_destino')),
            'planilla' => $this->nullIfEmpty($planilla),
            'flete' => $this->nullIfEmpty($this->getValueFromRecord($record, 'flete')),
            'nombre_fletero' => $this->nullIfEmpty($this->getValueFromRecord($record, 'nombre_fletero')),
            'camion' => $this->nullIfEmpty($this->getValueFromRecord($record, 'cam')),
            'patente' => $patente,
            'fecha_salida' => $fechaSalida,
            'hora_salida' => $this->nullIfEmpty($this->getValueFromRecord($record, 'hora_salida')),
            'fecha_llegada' => $fechaLlegada,
            'hora_llegada' => $this->nullIfEmpty($this->getValueFromRecord($record, 'hora_entrada')),
            'diferencia_horas' => $this->nullIfEmpty($this->getValueFromRecord($record, 'diferencia_en_horas')),
            'distancia' => is_numeric($this->getValueFromRecord($record, 'dist')) ?
                $this->getValueFromRecord($record, 'dist') : null,
            'categoria_flete' => $this->nullIfEmpty($this->getValueFromRecord($record, 'cat_flete')),
            'cierre' => $this->nullIfEmpty($this->getValueFromRecord($record, 'cierre')),
            'status' => $this->nullIfEmpty($this->getValueFromRecord($record, 'status')),
            'puntaje' => is_numeric($this->getValueFromRecord($record, 'ptaje_paleta')) ?
                $this->getValueFromRecord($record, 'ptaje_paleta') : null,
            'tarifa' => is_numeric($this->getValueFromRecord($record, 'tarif_adic')) ?
                $this->getValueFromRecord($record, 'tarif_adic') : null,
            'cod_producto' => $this->nullIfEmpty($codProducto),
            'producto' => $this->nullIfEmpty($this->getValueFromRecord($record, 'producto')),
            'salida' => is_numeric($this->getValueFromRecord($record, 'sal')) ?
                $this->getValueFromRecord($record, 'sal') : null,
            'entrada' => is_numeric($this->getValueFromRecord($record, 'ent')) ?
                $this->getValueFromRecord($record, 'ent') : null,
            'valor_producto' => is_numeric($this->getValueFromRecord($record, 'valor_por_producto')) ?
                $this->getValueFromRecord($record, 'valor_por_producto') : null,
            'variedad' => $this->nullIfEmpty($this->getValueFromRecord($record, 'variedad')),
            'linea' => $this->nullIfEmpty($this->getValueFromRecord($record, 'linea')),
            'tipo' => $this->nullIfEmpty($this->getValueFromRecord($record, 'tip_ord')),
            'numero_orden' => $this->nullIfEmpty($this->getValueFromRecord($record, 'numero_orden')),
            'fecha_orden' => $fechaOrden,
            'batch_id' => $this->batchId,
            'file_name' => $this->fileName,
            'fecha_registro' => $this->fechaHora,
            'final_status' => "1",
        ];
    }

    /**
     * Obtiene un valor de un registro utilizando mapeo flexible de nombres de columna
     */
    private function getValueFromRecord($record, $key)
    {
        // Buscar directamente por clave
        if (isset($record[$key])) {
            return $record[$key];
        }

        // Buscar en mapeos alternativos
        if (isset($this->columnMappings[$key])) {
            foreach ($this->columnMappings[$key] as $alternativeKey) {
                if (isset($record[$alternativeKey])) {
                    return $record[$alternativeKey];
                }
            }
        }

        // Buscar coincidencias parciales
        foreach ($record as $colName => $value) {
            if (strpos($colName, $key) !== false) {
                return $value;
            }
        }

        return null;
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
            foreach ($records as $newData) {
                // Verificar si existe registro
                $existingRecord = DB::table('trucks')
                    ->where('planilla', $newData['planilla'])
                    ->where('patente', $newData['patente'])
                    ->where('cod_producto', $newData['cod_producto'] ?? '')
                    ->first();

                if ($existingRecord) {
                    // Convertir a array para comparación
                    $existingArray = (array) $existingRecord;

                    // Comparar campos
                    $changes = [];
                    foreach ($newData as $key => $value) {
                        if (isset($existingArray[$key]) && $existingArray[$key] != $value &&
                            !in_array($key, ['batch_id', 'file_name', 'fecha_registro'])) {
                            $changes[$key] = $value;
                        }
                    }

                    if (!empty($changes)) {
                        // Guardar histórico
                        TruckHistory::create([
                            'planilla' => $existingArray['planilla'],
                            'patente' => $existingArray['patente'],
                            'cod_producto' => $existingArray['cod_producto'],
                            'fecha_salida' => $existingArray['fecha_salida'],
                            'batch_id' => $existingArray['batch_id'],
                            'original_data' => json_encode($existingArray),
                            'change_type' => 'UPDATE',
                            'changed_at' => now(),
                        ]);

                        // Actualizar registro
                        DB::table('trucks')
                            ->where('id', $existingArray['id'])
                            ->update($newData);
                    }
                } else {
                    // Crear nuevo registro
                    Truck::create($newData);
                }

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
     * Procesa el archivo usando Excel Import
     */
    /**
     * Procesa el archivo usando Excel Import con manejo directo (sin colas)
     */
    protected function processWithExcelImport($absolutePath)
    {
        // Estimar número de filas para el archivo
        $totalRecords = $this->estimateNumberOfRecords($absolutePath);

        // Crear archivo de bloqueo con información mejorada
        $this->createLockFile('truck', $totalRecords);

        Log::info('Starting import process', [
            'file' => $this->fileName,
            'batch_id' => $this->batchId,
            'estimated_records' => $totalRecords
        ]);

        // Procesamiento optimizado de cabeceras
        $tempFile = $this->preprocessHeaders($absolutePath);

        // Si el preprocesamiento creó un archivo temporal, úsalo
        $importPath = $tempFile ?: $absolutePath;

        try {
            // Importante: Usar import sin callback para evitar serialización de closure
            // En vez de eso, procesamos directamente el archivo
            $import = new TruckPlanImport(
                $this->fileName,
                $this->batchId,
                $this->fechaHora,
                $this->filePath
            );

            // Usar importToArray en lugar de import estándar
//            $rows = Excel::toArray($import, $importPath)[0];
            $rows = Excel::toArray($import, $importPath, null, \Maatwebsite\Excel\Excel::CSV)[0];

            // Procesar filas manualmente en lotes
            $processedRows = 0;
            $totalRows = count($rows);
            $batchSize = 100;

            for ($i = 0; $i < $totalRows; $i += $batchSize) {
                $batch = array_slice($rows, $i, $batchSize);

                foreach ($batch as $row) {
                    // Procesar cada fila usando el método model del importador
                    $import->model($row);
                }

                // Actualizar progreso
                $processedRows += count($batch);
                $this->updateLockFileProgress('truck', $processedRows);

                // Liberar memoria
                unset($batch);
                gc_collect_cycles();
            }

            // Limpiar archivo temporal si existe
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }

            // Actualizar el progreso al 100%
            $this->updateLockFileProgress('truck', $totalRecords);

            Log::info('Excel import completed successfully', [
                'file' => $this->fileName,
                'processed_rows' => $processedRows
            ]);

        } catch (\Exception $e) {
            // Limpiar archivo temporal en caso de error
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }

            Log::error('Error durante la importación Excel: ' . $e->getMessage(), [
                'file' => $this->fileName,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Asegurarse de eliminar el archivo de bloqueo si el job falla
        $this->removeLockFile('truck');

        Log::error('Job failed: ' . $exception->getMessage(), [
            'file' => $this->fileName,
            'batch_id' => $this->batchId,
            'exception' => $exception
        ]);
    }

    /**
     * Preprocesa las cabeceras del archivo CSV
     *
     * @param string $filePath
     * @return string|null Ruta del archivo temporal o null si no se pudo crear
     */
    private function preprocessHeaders(string $filePath): ?string
    {
        try {
            // Abre el archivo para lectura
            if (($handle = fopen($filePath, 'r')) === false) {
                Log::error('No se pudo abrir el archivo CSV.');
                return null;
            }

            // Lee las dos primeras filas
            $firstRow = fgetcsv($handle, 0, ',');
            $secondRow = fgetcsv($handle, 0, ',');

            if (!$firstRow || !$secondRow) {
                fclose($handle);
                Log::error('No se pudieron leer las cabeceras.');
                return null;
            }

            // Combina y limpia cabeceras de forma eficiente
            $cleanedHeaders = [];
            foreach ($firstRow as $index => $header1) {
                $header2 = $secondRow[$index] ?? '';
                $combined = trim(($header1 ?? '') . ' ' . $header2);
                $cleanedHeaders[] = rtrim(preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($combined)), '_');
            }

            // Crear archivo temporal
            $tempFile = tempnam(sys_get_temp_dir(), 'csv');
            $tempHandle = fopen($tempFile, 'w');

            // Escribe la cabecera
            fputcsv($tempHandle, $cleanedHeaders);

            // Lee el resto del archivo y cópialo al temporal
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                fputcsv($tempHandle, $row);
            }

            // Cierra ambos archivos
            fclose($tempHandle);
            fclose($handle);

            Log::info('Cabeceras actualizadas correctamente.');
            return $tempFile;

        } catch (\Exception $e) {
            Log::error('Error procesando cabeceras: ' . $e->getMessage());
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
     * Estima el número de registros en el archivo
     *
     * @param string $filePath
     * @return int
     */
    private function estimateNumberOfRecords(string $filePath): int
    {
        try {
            // Abrir el archivo
            $file = new \SplFileObject($filePath, 'r');

            // Contar líneas (menos 2 para las cabeceras)
            $lineCount = 0;
            $file->setFlags(\SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);

            foreach ($file as $line) {
                $lineCount++;
                // Para archivos grandes, estimar basado en tamaño de muestra
                if ($lineCount >= 1000) {
                    $currentPosition = $file->ftell();
                    $fileSize = filesize($filePath);

                    // Estimar el número total de líneas basado en la muestra
                    $estimatedLines = (int) ($lineCount * ($fileSize / $currentPosition));

                    // Restar 2 para las cabeceras y aplicar un factor de seguridad
                    return max(0, $estimatedLines - 2);
                }
            }

            // Para archivos pequeños, devolver el conteo real
            return max(0, $lineCount - 2); // Restar las dos filas de cabecera

        } catch (\Exception $e) {
            Log::warning('Error estimando número de registros: ' . $e->getMessage());
            return 0; // Valor por defecto si no se puede estimar
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
     * Limpia y normaliza una patente
     */
    private function cleanPatente($value)
    {
        if (empty($value)) {
            return null;
        }

        return str_replace([' ', '-'], '', trim($value));
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
            // Verificar si es numérico (formato Excel)
            if (is_numeric($value)) {
                // Fecha Excel (días desde 1899-12-30)
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                return $date->format('Y-m-d');
            }

            // Intentar formato d/m/y
            if (preg_match('/\d{1,2}\/\d{1,2}\/\d{2}$/', $value)) {
                try {
                    return Carbon::createFromFormat('d/m/y', $value)->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            }

            // Intentar formato d/m/Y
            if (preg_match('/\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
                try {
                    return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Error transformando fecha: " . $value . " - " . $e->getMessage());
            return null;
        }

        return null;
    }
}
