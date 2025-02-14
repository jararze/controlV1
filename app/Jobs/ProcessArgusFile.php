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

class ProcessArgusFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
            // Desactivar foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Truncar la tabla
            DB::table('arguses')->truncate();

            // Reactivar foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');


            // Verificar que el archivo existe
            $fullPath = storage_path('app/public/' . $this->filePath);
            if (!file_exists($fullPath)) {
                throw new \Exception("El archivo no existe en la ruta: " . $fullPath);
            }

            // Importar el archivo
            $import = new ArgusPlanImport($this->fileName, $this->batchId, $this->fechaHora);
            Excel::import($import, $fullPath);

            // Verificar que se importaron registros
            $recordCount = Argus::count();

            if ($recordCount === 0) {
                throw new \Exception('No se importaron registros');
            }



        } catch (\Exception $e) {
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
}
