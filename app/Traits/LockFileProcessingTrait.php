<?php

namespace App\Traits;

use Carbon\Carbon;

trait LockFileProcessingTrait
{
    /**
     * Create a lock file to indicate a running process
     *
     * @param string $type Tipo de proceso (truck, argus)
     * @return void
     */
    protected function createLockFile(string $type, int $totalRecords = 0)
    {
        $directory = storage_path('app/locks');

        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        $lockPath = $directory . '/' . $type . '_processing.lock';

        // Calcular un tiempo estimado de finalización basado en el tipo de archivo y tamaño
        $estimatedTimeMinutes = $this->calculateEstimatedTime($type, $totalRecords);
        $estimatedEndTime = now()->addMinutes($estimatedTimeMinutes);

        // Guardar información detallada del job en el archivo de bloqueo
        file_put_contents($lockPath, json_encode([
            'batch_id' => $this->batchId ?? null,
            'file_name' => $this->fileName ?? null,
            'started_at' => now()->toDateTimeString(),
            'estimated_end_time' => $estimatedEndTime->toDateTimeString(),
            'estimated_minutes' => $estimatedTimeMinutes,
            'total_records' => $totalRecords,
            'processed_records' => 0,
            'type' => $type,
            'last_update' => now()->toDateTimeString()
        ]));
    }

    /**
     * Remove the lock file when process completes
     *
     * @param string $type Tipo de proceso (truck, argus)
     * @return void
     */
    protected function removeLockFile(string $type)
    {
        $lockPath = storage_path('app/locks/' . $type . '_processing.lock');

        if (file_exists($lockPath)) {
            unlink($lockPath);
        }
    }

    /**
     * Update the lock file with progress information
     *
     * @param string $type Tipo de proceso (truck, argus)
     * @param int $processedRecords Número de registros procesados hasta ahora
     * @return void
     */
    protected function updateLockFileProgress(string $type, int $processedRecords)
    {
        $lockPath = storage_path('app/locks/' . $type . '_processing.lock');

        if (file_exists($lockPath)) {
            $lockData = json_decode(file_get_contents($lockPath), true);

            if ($lockData) {
                $lockData['processed_records'] = $processedRecords;
                $lockData['last_update'] = now()->toDateTimeString();

                // Recalcular tiempo estimado basado en el progreso actual
                if ($lockData['total_records'] > 0 && $processedRecords > 0) {
                    $percentComplete = min(99, ($processedRecords / $lockData['total_records']) * 100);

                    // Si ya hemos procesado suficientes registros para hacer una estimación
                    if ($percentComplete > 5) {
                        $timeElapsed = Carbon::parse($lockData['started_at'])->diffInSeconds(now());
                        $estimatedTotalTime = ($timeElapsed / $percentComplete) * 100;
                        $timeRemaining = $estimatedTotalTime - $timeElapsed;

                        $lockData['estimated_end_time'] = now()->addSeconds($timeRemaining)->toDateTimeString();
                        $lockData['estimated_minutes'] = ceil($timeRemaining / 60);
                    }
                }

                file_put_contents($lockPath, json_encode($lockData));
            }
        }
    }

    /**
     * Calculate estimated processing time based on file type and size
     *
     * @param string $type Tipo de proceso (truck, argus)
     * @param int $totalRecords Número de registros
     * @return int Tiempo estimado en minutos
     */
    private function calculateEstimatedTime(string $type, int $totalRecords): int
    {
        // Valores base por tipo de archivo (ajusta estos valores según tu experiencia)
        $baseTimePerRecord = [
            'truck' => 0.02,  // 20 ms por registro
            'argus' => 0.01   // 10 ms por registro
        ];

        // Si no conocemos el total de registros, hacer una estimación conservadora
        if ($totalRecords <= 0) {
            return $type === 'truck' ? 15 : 10; // Valores por defecto en minutos
        }

        // Calcular tiempo en minutos (con un mínimo de 1 minuto)
        $estimatedMinutes = ceil(($baseTimePerRecord[$type] ?? 0.015) * $totalRecords / 60);

        // Añadir un margen de seguridad
        $estimatedMinutes = max(1, $estimatedMinutes) * 1.2;

        return (int) $estimatedMinutes;
    }
}
