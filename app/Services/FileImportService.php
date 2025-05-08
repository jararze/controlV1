<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Carbon\Carbon;

class FileImportService
{
    /**
     * Directly import a file to the database
     */
    public function importFileToDatabase($filePath, $tableName, $batchId, $fileName, $dateTime)
    {
        $absolutePath = $this->getAbsolutePath($filePath);

        if (!file_exists($absolutePath)) {
            throw new \Exception("File not found: $absolutePath");
        }

        // Check file type and convert if necessary
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($extension === 'xlsx' || $extension === 'xls') {
            $csvPath = $this->convertExcelToCsv($absolutePath);
        } else {
            $csvPath = $absolutePath;
        }

        // Create a temp copy with proper UTF-8 encoding and cleaned headers
        $cleanedCsvPath = $this->createCleanCsvFile($csvPath);

        // Determine database connection type
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            return $this->importCsvToMysql($cleanedCsvPath, $tableName, $batchId, $fileName, $dateTime);
        } elseif ($driver === 'pgsql') {
            return $this->importCsvToPostgresql($cleanedCsvPath, $tableName, $batchId, $fileName, $dateTime);
        } elseif ($driver === 'sqlite') {
            return $this->importCsvToSqlite($cleanedCsvPath, $tableName, $batchId, $fileName, $dateTime);
        } else {
            return $this->importCsvWithPhp($cleanedCsvPath, $tableName, $batchId, $fileName, $dateTime);
        }
    }

    /**
     * Import CSV to MySQL using LOAD DATA INFILE (very fast)
     */
    private function importCsvToMysql($csvPath, $tableName, $batchId, $fileName, $dateTime)
    {
        $escapedCsvPath = str_replace('\\', '\\\\', $csvPath);

        // Check if local_infile is enabled
        $localInfile = DB::select("SHOW VARIABLES LIKE 'local_infile'")[0]->Value ?? 'OFF';

        if ($localInfile !== 'ON') {
            // Fall back to PHP method if local_infile is disabled
            return $this->importCsvWithPhp($csvPath, $tableName, $batchId, $fileName, $dateTime);
        }

        try {
            // Get column headers from the CSV
            $headers = $this->getHeadersFromCsv($csvPath);

            // Map CSV headers to table columns
            $columnMap = $this->mapHeadersToColumns($headers, $tableName);

            // Create a temporary table with same structure as target table
            $tempTableName = $tableName . '_temp_' . uniqid();
            DB::statement("CREATE TEMPORARY TABLE {$tempTableName} LIKE {$tableName}");

            // Build the SET clause for mapping
            $setClauses = [];
            foreach ($columnMap as $csvHeader => $dbColumn) {
                $setClauses[] = "{$dbColumn} = @col{$csvHeader}";
            }

            // Add metadata fields
            $setClauses[] = "batch_id = '{$batchId}'";
            $setClauses[] = "file_name = '{$fileName}'";
            $setClauses[] = "fecha_registro = '{$dateTime}'";
            $setClauses[] = "final_status = '1'";
            $setClauses[] = "created_at = NOW()";
            $setClauses[] = "updated_at = NOW()";

            $setClause = implode(', ', $setClauses);

            // Create variables for each column
            $columnVars = [];
            foreach (array_keys($columnMap) as $index => $header) {
                $columnVars[] = "@col{$header}";
            }

            $columnVarsStr = implode(', ', $columnVars);

            // Load data directly into the temporary table
            $sql = "
                LOAD DATA LOCAL INFILE '{$escapedCsvPath}'
                INTO TABLE {$tempTableName}
                FIELDS TERMINATED BY ','
                ENCLOSED BY '\"'
                LINES TERMINATED BY '\\n'
                IGNORE 1 LINES
                ({$columnVarsStr})
                SET {$setClause}
            ";

            DB::connection()->getpdo()->exec($sql);

            // Count the records
            $count = DB::table($tempTableName)->count();

            // Process dates
            if ($tableName == 'excesos') {
                // Process Excel dates for FECHA_EXCESO
                DB::statement("
                    UPDATE {$tempTableName}
                    SET FECHA_EXCESO = STR_TO_DATE(FECHA_EXCESO, '%d/%m/%Y')
                    WHERE FECHA_EXCESO REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$'
                ");

                // Handle Excel dates as numbers
                DB::statement("
                    UPDATE {$tempTableName}
                    SET FECHA_EXCESO = DATE_ADD('1899-12-30', INTERVAL CAST(FECHA_EXCESO AS UNSIGNED) DAY)
                    WHERE FECHA_EXCESO REGEXP '^[0-9]+$'
                ");

                // Process Excel dates for FECHA_RESTITUCION
                DB::statement("
                    UPDATE {$tempTableName}
                    SET FECHA_RESTITUCION = STR_TO_DATE(FECHA_RESTITUCION, '%d/%m/%Y')
                    WHERE FECHA_RESTITUCION REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$'
                ");

                // Handle Excel dates as numbers
                DB::statement("
                    UPDATE {$tempTableName}
                    SET FECHA_RESTITUCION = DATE_ADD('1899-12-30', INTERVAL CAST(FECHA_RESTITUCION AS UNSIGNED) DAY)
                    WHERE FECHA_RESTITUCION REGEXP '^[0-9]+$'
                ");
            } else if ($tableName == 'limites') {
                // Similar date processing for limites table
                DB::statement("
                    UPDATE {$tempTableName}
                    SET FECHA_ALERTA = STR_TO_DATE(FECHA_ALERTA, '%d/%m/%Y')
                    WHERE FECHA_ALERTA REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$'
                ");

                DB::statement("
                    UPDATE {$tempTableName}
                    SET FECHA_ALERTA = DATE_ADD('1899-12-30', INTERVAL CAST(FECHA_ALERTA AS UNSIGNED) DAY)
                    WHERE FECHA_ALERTA REGEXP '^[0-9]+$'
                ");
            }

            // Move data to the final table
            $columns = Schema::getColumnListing($tableName);
            $columnsString = implode(', ', $columns);

            DB::statement("INSERT INTO {$tableName} SELECT {$columnsString} FROM {$tempTableName}");

            // Drop the temporary table
            DB::statement("DROP TEMPORARY TABLE IF EXISTS {$tempTableName}");

            return $count;

        } catch (\Exception $e) {
            Log::error("Error importing CSV to MySQL: " . $e->getMessage(), [
                'file' => $fileName,
                'trace' => $e->getTraceAsString()
            ]);

            // Drop the temporary table if it exists
            DB::statement("DROP TEMPORARY TABLE IF EXISTS {$tempTableName}");

            return false;
        }
    }

    /**
     * Import CSV to PostgreSQL using COPY command (very fast)
     */
    private function importCsvToPostgresql($csvPath, $tableName, $batchId, $fileName, $dateTime)
    {
        try {
            // Create a temporary table with same structure as target table
            $tempTableName = $tableName . '_temp_' . uniqid();
            DB::statement("CREATE TEMP TABLE {$tempTableName} (LIKE {$tableName})");

            // Get CSV headers
            $headers = $this->getHeadersFromCsv($csvPath);

            // Map CSV headers to table columns
            $columnMap = $this->mapHeadersToColumns($headers, $tableName);

            // Create column list
            $columns = array_values($columnMap);
            $columnsString = implode(', ', $columns);

            // Use the \COPY command (faster than PHP)
            $pdo = DB::connection()->getPdo();
            $pdo->exec("
                COPY {$tempTableName} ({$columnsString})
                FROM '{$csvPath}'
                WITH (FORMAT csv, HEADER true, DELIMITER ',', QUOTE '\"')
            ");

            // Update metadata
            DB::table($tempTableName)->update([
                'batch_id' => $batchId,
                'file_name' => $fileName,
                'fecha_registro' => $dateTime,
                'final_status' => '1',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Process dates (different syntax for PostgreSQL)
            if ($tableName == 'excesos') {
                // Process dates (specific to PostgreSQL)
                DB::statement("
                    UPDATE {$tempTableName}
                    SET \"FECHA_EXCESO\" = TO_DATE(\"FECHA_EXCESO\", 'DD/MM/YYYY')
                    WHERE \"FECHA_EXCESO\" ~ '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$'
                ");

                DB::statement("
                    UPDATE {$tempTableName}
                    SET \"FECHA_RESTITUCION\" = TO_DATE(\"FECHA_RESTITUCION\", 'DD/MM/YYYY')
                    WHERE \"FECHA_RESTITUCION\" ~ '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$'
                ");

                // Handle Excel dates
                DB::statement("
                    UPDATE {$tempTableName}
                    SET \"FECHA_EXCESO\" = DATE '1899-12-30' + (\"FECHA_EXCESO\"::integer)::interval
                    WHERE \"FECHA_EXCESO\" ~ '^[0-9]+$'
                ");

                DB::statement("
                    UPDATE {$tempTableName}
                    SET \"FECHA_RESTITUCION\" = DATE '1899-12-30' + (\"FECHA_RESTITUCION\"::integer)::interval
                    WHERE \"FECHA_RESTITUCION\" ~ '^[0-9]+$'
                ");
            }

            // Count the records
            $count = DB::table($tempTableName)->count();

            // Move data to the final table
            $allColumns = Schema::getColumnListing($tableName);
            $allColumnsString = implode(', ', $allColumns);

            DB::statement("INSERT INTO {$tableName} SELECT {$allColumnsString} FROM {$tempTableName}");

            // Drop the temporary table
            DB::statement("DROP TABLE IF EXISTS {$tempTableName}");

            return $count;

        } catch (\Exception $e) {
            Log::error("Error importing CSV to PostgreSQL: " . $e->getMessage(), [
                'file' => $fileName,
                'trace' => $e->getTraceAsString()
            ]);

            // Drop the temporary table if it exists
            DB::statement("DROP TABLE IF EXISTS {$tempTableName}");

            return false;
        }
    }

    /**
     * Import CSV to SQLite
     */
    private function importCsvToSqlite($csvPath, $tableName, $batchId, $fileName, $dateTime)
    {
        // SQLite doesn't have a direct import mechanism, use PHP
        return $this->importCsvWithPhp($csvPath, $tableName, $batchId, $fileName, $dateTime);
    }

    /**
     * Import CSV using PHP (slower, but works on all databases)
     */
    private function importCsvWithPhp($csvPath, $tableName, $batchId, $fileName, $dateTime)
    {
        try {
            // Open the CSV file
            $handle = fopen($csvPath, 'r');
            if ($handle === false) {
                throw new \Exception("Cannot open file: $csvPath");
            }

            // Read headers
            $headers = fgetcsv($handle);
            if ($headers === false) {
                throw new \Exception("Cannot read headers from CSV");
            }

            // Normalize headers
            $headers = array_map(function($header) {
                return strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '_', $header)));
            }, $headers);

            // Map headers to database columns
            $columnMap = $this->mapHeadersToColumns($headers, $tableName);

            // Use a transaction for faster inserts
            DB::beginTransaction();

            // Import in batches for better performance
            $batchSize = 1000;
            $batch = [];
            $totalImported = 0;

            while (($row = fgetcsv($handle)) !== false) {
                // Skip if row has fewer columns than headers
                if (count($row) < count($headers)) {
                    continue;
                }

                // Combine headers with values
                $data = array_combine($headers, $row);

                // Create record with mapped columns
                $record = [];
                foreach ($columnMap as $csvHeader => $dbColumn) {
                    $record[$dbColumn] = isset($data[$csvHeader]) ? $data[$csvHeader] : null;
                }

                // Add metadata
                $record['batch_id'] = $batchId;
                $record['file_name'] = $fileName;
                $record['fecha_registro'] = $dateTime;
                $record['final_status'] = '1';
                $record['created_at'] = now();
                $record['updated_at'] = now();

                // Process dates if needed
                if ($tableName == 'excesos') {
                    if (!empty($record['FECHA_EXCESO'])) {
                        $record['FECHA_EXCESO'] = $this->parseDate($record['FECHA_EXCESO']);
                    }

                    if (!empty($record['FECHA_RESTITUCION'])) {
                        $record['FECHA_RESTITUCION'] = $this->parseDate($record['FECHA_RESTITUCION']);
                    }
                } else if ($tableName == 'limites') {
                    if (!empty($record['FECHA_ALERTA'])) {
                        $record['FECHA_ALERTA'] = $this->parseDate($record['FECHA_ALERTA']);
                    }
                }

                $batch[] = $record;

                // Insert batch when it reaches the batch size
                if (count($batch) >= $batchSize) {
                    DB::table($tableName)->insert($batch);
                    $totalImported += count($batch);
                    $batch = [];
                }
            }

            // Insert any remaining records
            if (count($batch) > 0) {
                DB::table($tableName)->insert($batch);
                $totalImported += count($batch);
            }

            fclose($handle);
            DB::commit();

            return $totalImported;

        } catch (\Exception $e) {
            Log::error("Error importing CSV with PHP: " . $e->getMessage(), [
                'file' => $fileName,
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }

            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return false;
        }
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // If it's a numeric Excel date
            if (is_numeric($value)) {
                // Excel dates are days since 1899-12-30
                return Carbon::createFromDate(1899, 12, 30)->addDays((int)$value);
            }

            // Try common date formats
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
                return Carbon::createFromFormat('d/m/Y', $value);
            }

            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{2}$/', $value)) {
                return Carbon::createFromFormat('d/m/y', $value);
            }

            if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $value)) {
                return Carbon::createFromFormat('Y-m-d', $value);
            }

            // Try generic parsing
            return Carbon::parse($value);

        } catch (\Exception $e) {
            Log::warning("Could not parse date: $value - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Map CSV headers to database columns
     */
    private function mapHeadersToColumns($headers, $tableName)
    {
        // Get database columns
        $dbColumns = Schema::getColumnListing($tableName);

        // Convert to lowercase for case-insensitive matching
        $dbColumnsLower = array_map('strtolower', $dbColumns);

        // Map headers to columns
        $columnMap = [];
        foreach ($headers as $header) {
            // Normalize header
            $normalizedHeader = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '_', $header)));

            // Find matching column (direct match)
            $columnIndex = array_search($normalizedHeader, $dbColumnsLower);
            if ($columnIndex !== false) {
                $columnMap[$normalizedHeader] = $dbColumns[$columnIndex];
                continue;
            }

            // Try matching by removing underscores
            $noUnderscores = str_replace('_', '', $normalizedHeader);
            foreach ($dbColumnsLower as $index => $column) {
                if (str_replace('_', '', $column) === $noUnderscores) {
                    $columnMap[$normalizedHeader] = $dbColumns[$index];
                    continue 2;
                }
            }

            // Final attempt - check if header is contained in any column
            foreach ($dbColumnsLower as $index => $column) {
                if (strpos($column, $normalizedHeader) !== false ||
                    strpos($normalizedHeader, $column) !== false) {
                    $columnMap[$normalizedHeader] = $dbColumns[$index];
                    continue 2;
                }
            }
        }

        return $columnMap;
    }

    /**
     * Convert Excel file to CSV
     */
    private function convertExcelToCsv($excelPath)
    {
        // Create a temp file for the CSV
        $csvPath = tempnam(sys_get_temp_dir(), 'excel_csv') . '.csv';

        try {
            // Use command-line converter if available (much faster and less memory-intensive)
            if ($this->isCommandAvailable('ssconvert')) { // Part of gnumeric package
                $process = new Process(['ssconvert', $excelPath, $csvPath]);
                $process->run();

                if (!$process->isSuccessful()) {
                    throw new \Exception("Error converting Excel to CSV using ssconvert");
                }
            } elseif ($this->isCommandAvailable('xlsx2csv')) { // Python xlsx2csv
                $process = new Process(['xlsx2csv', $excelPath, $csvPath]);
                $process->run();

                if (!$process->isSuccessful()) {
                    throw new \Exception("Error converting Excel to CSV using xlsx2csv");
                }
            } else {
                // Fall back to PHP (slower and more memory-intensive)
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                $reader->setReadDataOnly(true);

                $spreadsheet = $reader->load($excelPath);
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
                $writer->setDelimiter(',');
                $writer->setEnclosure('"');
                $writer->setLineEnding("\n");
                $writer->setSheetIndex(0);
                $writer->save($csvPath);

                // Free up memory
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }

            return $csvPath;

        } catch (\Exception $e) {
            Log::error("Error converting Excel to CSV: " . $e->getMessage(), [
                'excel_path' => $excelPath,
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Create a clean CSV file with proper encoding and headers
     */
    private function createCleanCsvFile($csvPath)
    {
        $cleanPath = tempnam(sys_get_temp_dir(), 'clean_csv') . '.csv';

        try {
            // Open the input and output files
            $input = fopen($csvPath, 'r');
            $output = fopen($cleanPath, 'w');

            if ($input === false || $output === false) {
                throw new \Exception("Cannot open files for cleaning CSV");
            }

            // Read headers
            $headers = fgetcsv($input);
            if ($headers === false) {
                throw new \Exception("Cannot read headers from CSV");
            }

            // Clean and normalize headers
            $cleanHeaders = [];
            foreach ($headers as $header) {
                // Clean non-ASCII characters
                $clean = preg_replace('/[^\x20-\x7E]/', '', $header);
                // Convert to lowercase
                $clean = strtolower(trim($clean));
                // Replace non-alphanumeric with underscore
                $clean = preg_replace('/[^a-z0-9]/', '_', $clean);
                // Remove consecutive underscores
                $clean = preg_replace('/_+/', '_', $clean);
                // Remove leading/trailing underscores
                $clean = trim($clean, '_');

                $cleanHeaders[] = $clean;
            }

            // Write clean headers
            fputcsv($output, $cleanHeaders);

            // Process and write data rows
            while (($row = fgetcsv($input)) !== false) {
                // Clean row data
                $cleanRow = array_map(function($cell) {
                    // Convert to UTF-8 if needed
                    if (!mb_check_encoding($cell, 'UTF-8')) {
                        $cell = mb_convert_encoding($cell, 'UTF-8', 'auto');
                    }
                    return $cell;
                }, $row);

                // Write to output file
                fputcsv($output, $cleanRow);
            }

            // Close files
            fclose($input);
            fclose($output);

            return $cleanPath;

        } catch (\Exception $e) {
            Log::error("Error cleaning CSV file: " . $e->getMessage(), [
                'csv_path' => $csvPath,
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($input) && is_resource($input)) {
                fclose($input);
            }

            if (isset($output) && is_resource($output)) {
                fclose($output);
            }

            throw $e;
        }
    }

    /**
     * Get absolute path from relative path
     */
    private function getAbsolutePath($filePath)
    {
        // First try the storage path
        $absolutePath = storage_path('app' . DIRECTORY_SEPARATOR . $filePath);
        $absolutePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $absolutePath);
        $absolutePath = preg_replace('/' . preg_quote(DIRECTORY_SEPARATOR, '/') . '{2,}/', DIRECTORY_SEPARATOR, $absolutePath);

        if (file_exists($absolutePath)) {
            return $absolutePath;
        }

        // Try the public storage path
        $publicPath = public_path('storage' . DIRECTORY_SEPARATOR . str_replace('public/', '', $filePath));
        $publicPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $publicPath);
        $publicPath = preg_replace('/' . preg_quote(DIRECTORY_SEPARATOR, '/') . '{2,}/', DIRECTORY_SEPARATOR, $publicPath);

        if (file_exists($publicPath)) {
            return $publicPath;
        }

        // If it's already an absolute path
        if (file_exists($filePath)) {
            return $filePath;
        }

        throw new \Exception("File not found: $filePath");
    }

    /**
     * Check if a command is available on the system
     */
    private function isCommandAvailable($command)
    {
        $whereIsCommand = PHP_OS_FAMILY === 'Windows' ? "where" : "which";

        $process = Process::fromShellCommandline("$whereIsCommand $command");
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Get headers from CSV file
     */
    private function getHeadersFromCsv($csvPath)
    {
        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            throw new \Exception("Cannot open file: $csvPath");
        }

        $headers = fgetcsv($handle);
        fclose($handle);

        if ($headers === false) {
            throw new \Exception("Cannot read headers from CSV");
        }

        // Normalize headers
        return array_map(function($header) {
            return strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '_', $header)));
        }, $headers);
    }
}
