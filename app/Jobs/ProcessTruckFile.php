<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Bus\Queueable;
use App\Traits\LockFileProcessingTrait;
use App\Services\UltraFastTruck;

class ProcessTruckFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LockFileProcessingTrait;

    public $tries = 3;
    public $timeout = 3600; // 1 hour timeout
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
     * Execute the job with ultra-fast processing using UltraFastTruck service.
     */
    public function handle(): void
    {
        // Optimize PHP settings for large files
        ini_set('memory_limit', '1024M');
        set_time_limit(3600);

        // Configure database connection for optimal performance
        $this->configureDatabaseConnection();

        // Configure PHP encoding
        ini_set('default_charset', 'UTF-8');
        mb_internal_encoding('UTF-8');
        mb_http_output('UTF-8');

        try {
            $absolutePath = storage_path('app/' . $this->filePath);

            Log::info('Starting ultra-fast truck file processing', [
                'file' => $this->fileName,
                'path' => $this->filePath,
                'absolute_path' => $absolutePath,
                'batch_id' => $this->batchId,
                'exists' => file_exists($absolutePath) ? 'Yes' : 'No',
                'size' => file_exists($absolutePath) ? $this->formatFileSize(filesize($absolutePath)) : 'N/A'
            ]);

            if (!file_exists($absolutePath)) {
                throw new \Exception('File not found at: ' . $absolutePath);
            }

            // Estimate record count for progress tracking
            $estimatedRecords = $this->estimateRecordCount($absolutePath);
            $this->createLockFile('truck', $estimatedRecords);

            Log::info('Processing with UltraFastTruck service', [
                'estimated_records' => $estimatedRecords,
                'service' => 'UltraFastTruck'
            ]);

            // Use the ultra-fast import service
            $ultraFastTruck = new UltraFastTruck();
            $results = $ultraFastTruck->importTruckFile(
                $this->filePath,
                $this->batchId,
                $this->fileName,
                $this->fechaHora
            );

            // Update progress to 100%
            $this->updateLockFileProgress('truck', $results['total_processed']);

            Log::info('Ultra-fast truck file processing completed successfully', [
                'batch_id' => $this->batchId,
                'file' => $this->fileName,
                'results' => $results
            ]);

            // Remove lock file after successful completion
            $this->removeLockFile('truck');

        } catch (\Exception $e) {
            // Always remove lock file on error
            $this->removeLockFile('truck');

            Log::error('Error in ultra-fast truck file processing', [
                'file' => $this->fileName,
                'batch_id' => $this->batchId,
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
        // Ensure lock file is removed on failure
        $this->removeLockFile('truck');

        Log::error('Ultra-fast truck processing job failed', [
            'file' => $this->fileName,
            'batch_id' => $this->batchId,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Estimate record count for progress tracking
     */
    private function estimateRecordCount($filePath): int
    {
        try {
            $extension = strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));

            if ($extension === 'csv') {
                // For CSV files, count lines
                $lineCount = 0;
                $handle = fopen($filePath, 'r');

                if ($handle) {
                    while (fgets($handle) !== false) {
                        $lineCount++;
                    }
                    fclose($handle);

                    // Subtract dual header rows (2 rows) plus one for safety
                    return max(0, $lineCount - 3);
                }
            } else {
                // For Excel files, estimate based on file size
                $fileSize = filesize($filePath);
                // Rough estimate: 1KB per row for Excel files
                return max(100, (int)($fileSize / 1024));
            }
        } catch (\Exception $e) {
            Log::warning('Could not estimate record count', [
                'file' => $this->fileName,
                'error' => $e->getMessage()
            ]);
        }

        return 1000; // Default estimate
    }

    /**
     * Format file size in human readable format
     */
    private function formatFileSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Configure database connection for optimal performance
     */
    private function configureDatabaseConnection()
    {
        try {
            // Basic UTF-8 configuration (safe for all databases)
            DB::statement('SET NAMES utf8mb4');
            DB::statement('SET CHARACTER SET utf8mb4');

            // MySQL-specific optimizations (with error handling)
            if (DB::getDriverName() === 'mysql') {
                // Safe session variables that don't require GLOBAL privileges
                $this->executeDatabaseStatement('SET SESSION collation_connection = utf8mb4_unicode_ci');
                $this->executeDatabaseStatement('SET SESSION wait_timeout = 28800');
                $this->executeDatabaseStatement('SET SESSION interactive_timeout = 28800');
                $this->executeDatabaseStatement('SET SESSION max_allowed_packet = 67108864'); // 64MB

                // Additional MySQL optimizations for bulk operations
                $this->executeDatabaseStatement('SET SESSION autocommit = 1');
                $this->executeDatabaseStatement('SET SESSION sql_mode = ""');
                $this->executeDatabaseStatement('SET SESSION foreign_key_checks = 1');

                // Performance optimizations (some may fail depending on MySQL version/config)
                $this->executeDatabaseStatement('SET SESSION bulk_insert_buffer_size = 16777216'); // 16MB
                $this->executeDatabaseStatement('SET SESSION myisam_sort_buffer_size = 67108864'); // 64MB

                Log::info('MySQL database connection optimized for bulk operations');
            }

        } catch (\Exception $e) {
            Log::warning('Some database optimizations could not be applied', [
                'error' => $e->getMessage()
            ]);
            // Continue execution - these are optimizations, not requirements
        }
    }

    /**
     * Execute database statement with error handling
     */
    private function executeDatabaseStatement($statement)
    {
        try {
            DB::statement($statement);
        } catch (\Exception $e) {
            Log::debug('Database statement failed (non-critical)', [
                'statement' => $statement,
                'error' => $e->getMessage()
            ]);
            // Continue - these are optimizations, not requirements
        }
    }
}
