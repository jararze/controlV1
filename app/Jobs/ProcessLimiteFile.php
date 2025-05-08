<?php

namespace App\Jobs;

use App\Imports\LimiteImport;
use App\Traits\LockFileProcessingTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ProcessLimiteFile implements ShouldQueue
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
    public function handle(): void
    {
        // Aumentar límites
        ini_set('memory_limit', '512M');
        set_time_limit(3600);
        DB::statement('SET SESSION wait_timeout = 28800');

        try {
            // Crear archivo de bloqueo
            $this->createLockFile('limite', 0);

            Log::info('Iniciando procesamiento de archivo Limite', [
                'file' => $this->fileName,
                'batch_id' => $this->batchId
            ]);

            // Importar el archivo
            Excel::import(
                new LimiteImport($this->fileName, $this->batchId, $this->fechaHora),
                $this->filePath,
                'public'
            );

            // Eliminar archivo de bloqueo
            $this->removeLockFile('limite');

            Log::info('Archivo Limite procesado correctamente', [
                'file' => $this->fileName,
                'batch_id' => $this->batchId
            ]);
        } catch (\Exception $e) {
            $this->removeLockFile('limite');

            Log::error('Error procesando archivo Limite', [
                'file' => $this->fileName,
                'error' => $e->getMessage(),
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
        $this->removeLockFile('limite');

        Log::error('Job ProcessLimiteFile falló', [
            'file' => $this->fileName,
            'error' => $exception->getMessage()
        ]);
    }
}
