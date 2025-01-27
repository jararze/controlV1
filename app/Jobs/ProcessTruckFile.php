<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
//use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\TruckPlanImport;
use Illuminate\Bus\Queueable;


class ProcessTruckFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        try {
            $absolutePath = storage_path('app/' . $this->filePath);

            if (!file_exists($absolutePath)) {
                Log::error('File not found: ' . $absolutePath);
                throw new \Exception('File not found at: ' . $absolutePath);
            }

            Log::info('Starting import process', [
                'file' => $this->fileName,
                'batch_id' => $this->batchId,
                'path' => $absolutePath
            ]);

            // Transformar cabeceras antes de la importación
            if (($handle = fopen($absolutePath, 'r')) !== false) {
                $firstRow = fgetcsv($handle, 0, ',');
                $secondRow = fgetcsv($handle, 0, ',');

                if ($firstRow && $secondRow) {
                    // Combinar y limpiar cabeceras
                    $cleanedHeaders = array_map(function ($header1, $header2) {
                        $combined = trim(($header1 ?? '') . ' ' . ($header2 ?? ''));
                        $cleaned = preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($combined));
                        return rtrim($cleaned, '_');
                    }, $firstRow, $secondRow);

                    // Guardar el archivo actualizado
                    $tempFile = tempnam(sys_get_temp_dir(), 'csv');
                    $tempHandle = fopen($tempFile, 'w');
                    fputcsv($tempHandle, $cleanedHeaders);

                    while (($row = fgetcsv($handle, 0, ',')) !== false) {
                        fputcsv($tempHandle, $row);
                    }

                    fclose($tempHandle);
                    fclose($handle);

                    rename($tempFile, $absolutePath); // Sobrescribe el archivo original
                    Log::info('Cabeceras actualizadas correctamente.');
                } else {
                    Log::error('No se pudieron leer las cabeceras.');
                    fclose($handle);
                    return;
                }
            } else {
                Log::error('No se pudo abrir el archivo CSV.');
                return;
            }

//            $import = new TruckPlanImport($this->fileName, $this->batchId, $this->fechaHora, $this->filePath);
//            Excel::import($import, $absolutePath, null, \Maatwebsite\Excel\Excel::CSV);
            Excel::import(
                new TruckPlanImport($this->fileName, $this->batchId, $this->fechaHora, $this->filePath),
                $absolutePath,
                null,
                \Maatwebsite\Excel\Excel::CSV
            );
            Log::info('File processed successfully', [
                'batch_id' => $this->batchId,
                'file' => $this->fileName
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing file: ' . $e->getMessage(), [
                'file' => $this->fileName,
                'batch_id' => $this->batchId,
                'exception' => $e
            ]);
            throw $e;
        }
    }
}
