<?php

namespace App\Jobs;

use App\Imports\MatrixPlanImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ProcessMatrixFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $batchId;
    protected $fechaHora;
    protected $fileName;


    // Aumentar el timeout y número de intentos
    public $timeout = 7200;    // 2 horas
    public $tries = 3;         // Número de intentos
    public $maxExceptions = 3; // Máximo número de excepciones antes de fallar
    public $backoff = [60, 180, 300]; // Esperar 1, 3 y 5 minutos entre intentos

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
     */
    public function handle(): void
    {
        try {

            $fullPath = storage_path('app/public/' . $this->filePath);
            if (!file_exists($fullPath)) {
                throw new \Exception("Archivo no encontrado en: " . $fullPath);
            }


            // Importar el archivo
            $import = new MatrixPlanImport($this->fileName, $this->batchId, $this->fechaHora);
            Excel::import($import, $fullPath);

            $this->delete();

        } catch (\Exception $e) {
            Log::error('Error al importar el archivo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $this->filePath
            ]);

            if ($this->attempts() >= $this->tries) {
                Log::critical('Último intento fallido para importar archivo', [
                    'batchId' => $this->batchId,
                    'fileName' => $this->fileName,
                    'totalAttempts' => $this->attempts()
                ]);
            }

            throw $e;

        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Job falló', [
            'error' => $exception->getMessage(),
            'file' => $this->filePath,
            'batchId' => $this->batchId
        ]);
    }

    public function retryUntil()
    {
        return now()->addHours(24); // El job puede ser reintentado hasta 24 horas después
    }

}
