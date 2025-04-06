<?php

namespace App\Jobs;

use App\Imports\ArgusPlanImport;
use App\Models\Argus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\LockFileProcessingTrait;

class ProcessArgusFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LockFileProcessingTrait;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600;

    /**
     * Indicate if the job should be marked as failed on timeout.
     *
     * @var bool
     */
    public $failOnTimeout = true;

    protected $filePath;
    protected $batchId;
    protected $fechaHora;
    protected $fileName;

    public function __construct($filePath, $batchId, $fechaHora, $fileName)
    {
        $this->filePath = $filePath;
        $this->batchId = $batchId;
        $this->fechaHora = $fechaHora;
        $this->fileName = $fileName;
    }

    public function handle()
    {
        try {
            // Verificar que el archivo existe
            $fullPath = storage_path('app/public/' . $this->filePath);
            if (!file_exists($fullPath)) {
                throw new \Exception("El archivo no existe en la ruta: " . $fullPath);
            }

            // Estimar número de registros en el archivo
            $totalRecords = $this->estimateNumberOfRecords($fullPath);

            // Crear archivo de bloqueo con información mejorada
            $this->createLockFile('argus', $totalRecords);

            Log::info('Starting Argus import process', [
                'file' => $this->fileName,
                'batch_id' => $this->batchId,
                'estimated_records' => $totalRecords
            ]);

            // Desactivar foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Truncar la tabla
            DB::table('arguses')->truncate();

            // Reactivar foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // Importar el archivo con monitoreo de progreso
            $import = new ArgusPlanImport($this->fileName, $this->batchId, $this->fechaHora);

            // Configurar el callback para actualizar el progreso
            $import->onChunkRead(function($processedRows) use ($totalRecords) {
                // Actualizar el archivo de bloqueo con el progreso
                $this->updateLockFileProgress('argus', $processedRows);
            });

            Excel::import($import, $fullPath);

            // Verificar que se importaron registros
            $recordCount = Argus::count();

            if ($recordCount === 0) {
                $this->removeLockFile('argus');
                throw new \Exception('No se importaron registros');
            }

            // Actualizar el progreso al 100% y eliminar archivo de bloqueo
            $this->updateLockFileProgress('argus', $totalRecords);
            $this->removeLockFile('argus');

            Log::info('Argus file processed successfully', [
                'file' => $this->fileName,
                'batch_id' => $this->batchId,
                'total_records' => $recordCount
            ]);

        } catch (\Exception $e) {
            // Eliminar archivo de bloqueo en caso de error
            $this->removeLockFile('argus');

            Log::error('Error procesando archivo Argus', [
                'error' => $e->getMessage(),
                'file' => $this->fileName,
                'batch_id' => $this->batchId,
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        // Eliminar archivo de bloqueo en caso de fallo
        $this->removeLockFile('argus');

        Log::error('Falló el job de procesamiento de Argus', [
            'error' => $exception->getMessage(),
            'file' => $this->fileName,
            'batch_id' => $this->batchId,
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware()
    {
        return [];
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

            // Contar líneas (menos 1 para la cabecera)
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

                    // Restar 1 para la cabecera y aplicar un factor de seguridad
                    return max(0, $estimatedLines - 1);
                }
            }

            // Para archivos pequeños, devolver el conteo real
            return max(0, $lineCount - 1); // Restar la fila de cabecera

        } catch (\Exception $e) {
            Log::warning('Error estimando número de registros en Argus: ' . $e->getMessage());
            return 0; // Valor por defecto si no se puede estimar
        }
    }
}
