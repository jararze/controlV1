<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class JobStatusChecker
{
    /**
     * Check if either ProcessArgusFile or ProcessTruckFile jobs are running
     *
     * @return bool
     */
    public function areJobsRunning()
    {
        // Usar caché para evitar consultas frecuentes (10 segundos)
        return Cache::remember('processing_jobs_running', 10, function () {
            // Para colas en base de datos
            if (config('queue.default') === 'database') {
                $pendingJobs = DB::table('jobs')
                    ->where(function ($query) {
                        $query->where('payload', 'like', '%ProcessTruckFile%')
                            ->orWhere('payload', 'like', '%ProcessArgusFile%');
                    })
                    ->count();

                return $pendingJobs > 0;
            }

            // Para Redis
            if (config('queue.default') === 'redis') {
                $redis = app('redis');
                $queues = ['default']; // La cola que usas

                foreach ($queues as $queue) {
                    $length = $redis->llen('queues:' . $queue);
                    if ($length > 0) {
                        return true;
                    }
                }
            }

            // Verificar archivos de bloqueo como método alternativo
            return $this->checkLockFiles();
        });
    }

    /**
     * Get detailed information about running jobs
     *
     * @return array
     */
    public function getRunningJobsInfo()
    {
        // Usar caché para evitar consultas frecuentes (5 segundos)
        return Cache::remember('processing_jobs_info', 5, function () {
            $result = [
                'jobs' => [],
                'total_progress' => 0,
                'overall_estimated_minutes' => 0
            ];

            $lockFiles = $this->getLockFilesInfo();

            if (!empty($lockFiles)) {
                $result['jobs'] = $lockFiles;

                // Calcular progreso total
                $totalProgress = 0;
                $totalJobs = count($lockFiles);
                $maxEstimatedMinutes = 0;

                foreach ($lockFiles as $job) {
                    if (isset($job['progress_percent'])) {
                        $totalProgress += $job['progress_percent'];
                    }

                    if (isset($job['estimated_minutes']) && $job['estimated_minutes'] > $maxEstimatedMinutes) {
                        $maxEstimatedMinutes = $job['estimated_minutes'];
                    }
                }

                $result['total_progress'] = $totalJobs > 0 ? round($totalProgress / $totalJobs) : 0;
                $result['overall_estimated_minutes'] = $maxEstimatedMinutes;
            }

            return $result;
        });
    }

    /**
     * Check if lock files exist for our processes
     *
     * @return bool
     */
    private function checkLockFiles()
    {
        $directory = storage_path('app/locks');

        // Si el directorio no existe, no hay jobs corriendo
        if (!file_exists($directory)) {
            return false;
        }

        $lockFiles = [
            'truck_processing.lock',
            'argus_processing.lock'
        ];

        foreach ($lockFiles as $lockFile) {
            if (file_exists($directory . '/' . $lockFile)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get detailed information from all lock files
     *
     * @return array
     */
    private function getLockFilesInfo()
    {
        $directory = storage_path('app/locks');

        if (!file_exists($directory)) {
            return [];
        }

        $result = [];
        $lockFiles = [
            'truck_processing.lock' => 'Truck',
            'argus_processing.lock' => 'Argus'
        ];

        foreach ($lockFiles as $lockFile => $jobType) {
            $filePath = $directory . '/' . $lockFile;

            if (file_exists($filePath)) {
                try {
                    $data = json_decode(file_get_contents($filePath), true);

                    if ($data) {
                        // Agregar etiqueta para identificación
                        $data['job_type'] = $jobType;

                        // Calcular el progreso
                        if (isset($data['total_records']) && $data['total_records'] > 0) {
                            $data['progress_percent'] = min(99, round(($data['processed_records'] / $data['total_records']) * 100));
                        } else {
                            $data['progress_percent'] = 0;
                        }

                        // Calcular tiempo transcurrido
                        if (isset($data['started_at'])) {
                            $startTime = Carbon::parse($data['started_at']);
                            $data['elapsed_minutes'] = $startTime->diffInMinutes(now());
                            $data['formatted_elapsed_time'] = $this->formatTimeInterval($data['elapsed_minutes']);
                        }

                        // Formatear tiempo estimado restante
                        if (isset($data['estimated_minutes'])) {
                            $data['formatted_estimated_time'] = $this->formatTimeInterval($data['estimated_minutes']);
                        }

                        // Verificar si el proceso está potencialmente bloqueado (sin actualización reciente)
                        if (isset($data['last_update'])) {
                            $lastUpdate = Carbon::parse($data['last_update']);
                            $data['minutes_since_update'] = $lastUpdate->diffInMinutes(now());
                            $data['potentially_stuck'] = $data['minutes_since_update'] > 5; // 5 minutos sin actualización
                        }

                        $result[] = $data;
                    }
                } catch (\Exception $e) {
                    // Si hay error al leer el archivo, continuar con el siguiente
                    continue;
                }
            }
        }

        return $result;
    }

    /**
     * Format time interval in a human readable format
     *
     * @param int $minutes
     * @return string
     */
    private function formatTimeInterval($minutes)
    {
        if ($minutes < 1) {
            return "menos de un minuto";
        } elseif ($minutes < 60) {
            return $minutes . " " . ($minutes == 1 ? "minuto" : "minutos");
        } else {
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;

            $result = $hours . " " . ($hours == 1 ? "hora" : "horas");

            if ($mins > 0) {
                $result .= " y " . $mins . " " . ($mins == 1 ? "minuto" : "minutos");
            }

            return $result;
        }
    }
}
