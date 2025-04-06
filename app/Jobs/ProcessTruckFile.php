<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\TruckPlanImport;
use Illuminate\Bus\Queueable;
use App\Traits\LockFileProcessingTrait;

class ProcessTruckFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LockFileProcessingTrait;

    public $tries = 3;  // Allow retries
    public $timeout = 3600;  // 1 hour timeout
    public $maxExceptions = 3;

    protected $filePath;
    protected $fileName;
    protected $batchId;
    protected $fechaHora;

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
        ini_set('memory_limit', '512M');

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

            // Crear una instancia del importador con callback de progreso
            $import = new TruckPlanImport(
                $this->fileName,
                $this->batchId,
                $this->fechaHora,
                $this->filePath
            );

            // Configurar el callback para actualizar el progreso
            $import->onChunkRead(function($processedRows) use ($totalRecords) {
                // Actualizar el archivo de bloqueo con el progreso
                $this->updateLockFileProgress('truck', $processedRows);
            });

            Excel::import(
                $import,
                $importPath,
                null,
                \Maatwebsite\Excel\Excel::CSV
            );

            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }

            // Actualizar el progreso al 100% y eliminar el archivo de bloqueo
            $this->updateLockFileProgress('truck', $totalRecords);
            $this->removeLockFile('truck');

            Log::info('File processed successfully', [
                'batch_id' => $this->batchId,
                'file' => $this->fileName,
                'total_records' => $totalRecords
            ]);

        } catch (\Exception $e) {
            // Eliminar el archivo de bloqueo en caso de error
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
}
