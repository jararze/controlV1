<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Truck;
use App\Models\TruckHistory;
use Carbon\Carbon;

class UltraFastTruck
{
    private $datePatterns;

    public function __construct()
    {
        // Definir patrones de fecha
        $this->datePatterns = [
            'dmy' => '/^\d{1,2}\/\d{1,2}\/\d{2}$/',
            'dmY' => '/^\d{1,2}\/\d{1,2}\/\d{4}$/',
        ];
    }

    /**
     * Ultra-fast truck file import using optimized database operations
     */
    public function importTruckFile($filePath, $batchId, $fileName, $fechaHora)
    {
        $startTime = microtime(true);

        Log::info("Starting ultra-fast truck import with direct CSV processing", [
            'file' => $fileName,
            'batch_id' => $batchId
        ]);

        try {
            // Create and populate temporary table using our direct method
            $tempTableName = $this->createAndPopulateTempTable($filePath, $batchId, $fileName, $fechaHora);

            // Process data with history tracking
            $results = $this->processDataWithHistoryORM($tempTableName, $batchId);

            $executionTime = round(microtime(true) - $startTime, 2);

            Log::info("Ultra-fast truck import completed", [
                'file' => $fileName,
                'execution_time' => $executionTime.'s',
                'stats' => $results
            ]);

            return array_merge($results, ['execution_time' => $executionTime]);

        } catch (\Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);

            Log::error("Error in ultra-fast truck import", [
                'file' => $fileName,
                'error' => $e->getMessage(),
                'execution_time' => $executionTime.'s',
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Create temporary table and populate with direct CSV import
     */
    private function createAndPopulateTempTable($filePath, $batchId, $fileName, $fechaHora)
    {
        $tempTableName = 'trucks_temp_' . uniqid();

        // Create temporary table with STRING columns for dates initially
        $this->createTempTableWithStringDates($tempTableName);

        // First, preprocess the file to handle dual-row headers
        $cleanFilePath = $this->preprocessFileHeaders($filePath);

        try {
            // Import directly with our optimized CSV processor
            $recordCount = $this->importCsvDirectly($cleanFilePath, $tempTableName, $batchId, $fileName, $fechaHora);

            // NUEVO: Limpiar espacios en blanco de TODAS las columnas VARCHAR
            $this->cleanAllColumns($tempTableName);

            // DESPUÉS: Procesar las fechas
            $this->processDateColumns($tempTableName);

            Log::info("Imported, cleaned and processed {$recordCount} records to temporary table", [
                'temp_table' => $tempTableName,
                'original_file' => $filePath,
                'processed_file' => $cleanFilePath
            ]);

            return $tempTableName;

        } finally {
            // Clean up temporary file
            if ($cleanFilePath !== $filePath && file_exists($cleanFilePath)) {
                unlink($cleanFilePath);
            }
        }
    }

    /**
     * Limpiar espacios en blanco de todas las columnas VARCHAR
     */
    private function cleanAllColumns($tempTableName)
    {
        Log::info("Cleaning whitespace from all VARCHAR columns", ['table' => $tempTableName]);

        try {
            // Limpiar TODAS las columnas VARCHAR de espacios en blanco
            DB::statement("
            UPDATE {$tempTableName}
            SET
                cod = TRIM(cod),
                deposito_origen = TRIM(deposito_origen),
                cod_destino = TRIM(cod_destino),
                deposito_destino = TRIM(deposito_destino),
                planilla = TRIM(planilla),
                flete = TRIM(flete),
                nombre_fletero = TRIM(nombre_fletero),
                camion = TRIM(camion),
                patente = TRIM(patente),
                fecha_salida_raw = TRIM(fecha_salida_raw),
                hora_salida_raw = TRIM(hora_salida_raw),
                fecha_llegada_raw = TRIM(fecha_llegada_raw),
                hora_llegada_raw = TRIM(hora_llegada_raw),
                diferencia_horas = TRIM(diferencia_horas),
                categoria_flete = TRIM(categoria_flete),
                cierre = TRIM(cierre),
                status = TRIM(status),
                cod_producto = TRIM(cod_producto),
                producto = TRIM(producto),
                variedad = TRIM(variedad),
                linea = TRIM(linea),
                tipo = TRIM(tipo),
                numero_orden = TRIM(numero_orden),
                fecha_orden_raw = TRIM(fecha_orden_raw),
                batch_id = TRIM(batch_id),
                file_name = TRIM(file_name),
                final_status = TRIM(final_status)
            WHERE 1=1
        ");

            // Verificar algunos campos importantes después de la limpieza
            $sampleCleaned = DB::select("
            SELECT
                CONCAT('\"', planilla, '\"') as planilla_cleaned,
                CONCAT('\"', patente, '\"') as patente_cleaned,
                CONCAT('\"', fecha_salida_raw, '\"') as fecha_salida_cleaned,
                CONCAT('\"', cod_producto, '\"') as cod_producto_cleaned
            FROM {$tempTableName}
            LIMIT 5
        ");

            Log::info("Sample data after cleaning all columns", [
                'sample_cleaned' => $sampleCleaned
            ]);

            // Contar registros con campos importantes no vacíos
            $cleanStats = DB::select("
            SELECT
                COUNT(*) as total_records,
                COUNT(CASE WHEN planilla IS NOT NULL AND planilla != '' THEN 1 END) as planilla_not_empty,
                COUNT(CASE WHEN patente IS NOT NULL AND patente != '' THEN 1 END) as patente_not_empty,
                COUNT(CASE WHEN cod_producto IS NOT NULL AND cod_producto != '' THEN 1 END) as cod_producto_not_empty
            FROM {$tempTableName}
        ")[0];

            Log::info("Column cleaning statistics", [
                'stats' => $cleanStats
            ]);

        } catch (\Exception $e) {
            Log::error("Error cleaning columns: " . $e->getMessage(), [
                'table' => $tempTableName,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }


    /**
     * Procesar columnas de fecha después de la importación
     */
    private function processDateColumns($tempTableName)
    {
        Log::info("Processing date columns", ['table' => $tempTableName]);

        try {
            // Ver qué datos tenemos en las columnas de fecha (ya están limpias)
            $sampleData = DB::select("
            SELECT
                fecha_salida_raw,
                hora_salida_raw,
                fecha_llegada_raw,
                hora_llegada_raw,
                fecha_orden_raw
            FROM {$tempTableName}
            LIMIT 5
        ");

            Log::info("Sample date data before processing", [
                'sample_data' => $sampleData
            ]);

            // Contar tipos de datos
            $dataTypes = DB::select("
            SELECT
                COUNT(*) as total_records,
                COUNT(CASE WHEN fecha_salida_raw REGEXP '^[0-9]+$' THEN 1 END) as fecha_salida_numeric,
                COUNT(CASE WHEN fecha_salida_raw REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$' THEN 1 END) as fecha_salida_dmy4,
                COUNT(CASE WHEN fecha_salida_raw REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{2}$' THEN 1 END) as fecha_salida_dmy2,
                COUNT(CASE WHEN fecha_salida_raw REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{1,4}$' THEN 1 END) as fecha_salida_dmy_any,
                COUNT(CASE WHEN hora_salida_raw REGEXP '^[0-9]+\.[0-9]+$' THEN 1 END) as hora_salida_decimal,
                COUNT(CASE WHEN hora_salida_raw REGEXP '^[0-9]{1,2}:[0-9]{2}' THEN 1 END) as hora_salida_time
            FROM {$tempTableName}
        ")[0];

            Log::info("Data type analysis", [
                'data_types' => $dataTypes
            ]);

            // Procesar fecha_salida
            Log::info("Processing fecha_salida...");
            DB::statement("
            UPDATE {$tempTableName}
            SET fecha_salida = CASE
                WHEN fecha_salida_raw REGEXP '^[0-9]+$' AND CAST(fecha_salida_raw AS UNSIGNED) > 0 THEN
                    DATE_ADD('1899-12-30', INTERVAL CAST(fecha_salida_raw AS UNSIGNED) DAY)
                WHEN fecha_salida_raw REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$' THEN
                    STR_TO_DATE(fecha_salida_raw, '%d/%m/%Y')
                WHEN fecha_salida_raw REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{2}$' THEN
                    STR_TO_DATE(fecha_salida_raw, '%d/%m/%y')
                WHEN fecha_salida_raw REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{1}$' THEN
                    STR_TO_DATE(CONCAT(fecha_salida_raw, '0'), '%d/%m/%y')
                ELSE NULL
            END
            WHERE fecha_salida_raw IS NOT NULL AND fecha_salida_raw != ''
        ");

            // Procesar hora_salida
            Log::info("Processing hora_salida...");
            DB::statement("
            UPDATE {$tempTableName}
            SET hora_salida = CASE
                WHEN hora_salida_raw REGEXP '^0\.[0-9]+$' THEN
                    SEC_TO_TIME(ROUND(CAST(hora_salida_raw AS DECIMAL(10,6)) * 86400))
                WHEN hora_salida_raw REGEXP '^[0-9]+\.[0-9]+$' THEN
                    SEC_TO_TIME(ROUND(CAST(hora_salida_raw AS DECIMAL(10,6)) * 86400))
                WHEN hora_salida_raw REGEXP '^[0-9]{1,2}:[0-9]{2}(:[0-9]{2})?$' THEN
                    TIME(hora_salida_raw)
                ELSE NULL
            END
            WHERE hora_salida_raw IS NOT NULL AND hora_salida_raw != ''
        ");

            // Procesar fecha_llegada
            Log::info("Processing fecha_llegada...");
            DB::statement("
            UPDATE {$tempTableName}
            SET fecha_llegada = CASE
                WHEN fecha_llegada_raw REGEXP '^[0-9]+$' AND CAST(fecha_llegada_raw AS UNSIGNED) > 0 THEN
                    DATE_ADD('1899-12-30', INTERVAL CAST(fecha_llegada_raw AS UNSIGNED) DAY)
                WHEN fecha_llegada_raw REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$' THEN
                    STR_TO_DATE(fecha_llegada_raw, '%d/%m/%Y')
                WHEN fecha_llegada_raw REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{2}$' THEN
                    STR_TO_DATE(fecha_llegada_raw, '%d/%m/%y')
                WHEN fecha_llegada_raw REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{1}$' THEN
                    STR_TO_DATE(CONCAT(fecha_llegada_raw, '0'), '%d/%m/%y')
                ELSE NULL
            END
            WHERE fecha_llegada_raw IS NOT NULL AND fecha_llegada_raw != ''
        ");

            // Procesar hora_llegada
            Log::info("Processing hora_llegada...");
            DB::statement("
            UPDATE {$tempTableName}
            SET hora_llegada = CASE
                WHEN hora_llegada_raw REGEXP '^0\.[0-9]+$' THEN
                    SEC_TO_TIME(ROUND(CAST(hora_llegada_raw AS DECIMAL(10,6)) * 86400))
                WHEN hora_llegada_raw REGEXP '^[0-9]+\.[0-9]+$' THEN
                    SEC_TO_TIME(ROUND(CAST(hora_llegada_raw AS DECIMAL(10,6)) * 86400))
                WHEN hora_llegada_raw REGEXP '^[0-9]{1,2}:[0-9]{2}(:[0-9]{2})?$' THEN
                    TIME(hora_llegada_raw)
                ELSE NULL
            END
            WHERE hora_llegada_raw IS NOT NULL AND hora_llegada_raw != ''
        ");

            // Procesar fecha_orden
            Log::info("Processing fecha_orden...");
            DB::statement("
            UPDATE {$tempTableName}
            SET fecha_orden = CASE
                WHEN fecha_orden_raw REGEXP '^[0-9]+$' AND CAST(fecha_orden_raw AS UNSIGNED) > 0 THEN
                    DATE_ADD('1899-12-30', INTERVAL CAST(fecha_orden_raw AS UNSIGNED) DAY)
                WHEN fecha_orden_raw REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$' THEN
                    CASE
                        WHEN SUBSTRING_INDEX(fecha_orden_raw, '/', 1) > 12 THEN
                            STR_TO_DATE(fecha_orden_raw, '%d/%m/%Y')
                        ELSE
                            STR_TO_DATE(fecha_orden_raw, '%m/%d/%Y')
                    END
                WHEN fecha_orden_raw REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{2}$' THEN
                    STR_TO_DATE(fecha_orden_raw, '%d/%m/%y')
                WHEN fecha_orden_raw REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{1}$' THEN
                    STR_TO_DATE(CONCAT(fecha_orden_raw, '0'), '%d/%m/%y')
                ELSE NULL
            END
            WHERE fecha_orden_raw IS NOT NULL AND fecha_orden_raw != ''
        ");

            // Verificar resultados
            $processedDates = DB::select("
            SELECT
                COUNT(*) as total_records,
                COUNT(fecha_salida) as fecha_salida_processed,
                COUNT(hora_salida) as hora_salida_processed,
                COUNT(fecha_llegada) as fecha_llegada_processed,
                COUNT(hora_llegada) as hora_llegada_processed,
                COUNT(fecha_orden) as fecha_orden_processed
            FROM {$tempTableName}
        ")[0];

            $sampleProcessed = DB::select("
            SELECT
                fecha_salida_raw, fecha_salida,
                hora_salida_raw, hora_salida,
                fecha_llegada_raw, fecha_llegada,
                hora_llegada_raw, hora_llegada,
                fecha_orden_raw, fecha_orden
            FROM {$tempTableName}
            LIMIT 5
        ");

            Log::info("Date processing completed", [
                'table' => $tempTableName,
                'stats' => $processedDates,
                'sample_processed' => $sampleProcessed
            ]);

        } catch (\Exception $e) {
            Log::error("Error processing date columns: " . $e->getMessage(), [
                'table' => $tempTableName,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Test manual de conversión de fechas
     */
    public function testDateConversion($testValues = null)
    {
        if ($testValues === null) {
            $testValues = [
                '45493',  // Fecha Excel típica
                '25/07/2024', // Fecha dd/mm/yyyy
                '25/07/24',   // Fecha dd/mm/yy
                '0.6017361111', // Hora Excel típica
                '14:26:30'    // Hora normal
            ];
        }

        Log::info("Testing date conversion with sample values", [
            'test_values' => $testValues
        ]);

        foreach ($testValues as $value) {
            Log::info("Testing value: {$value}", [
                'is_numeric' => is_numeric($value),
                'as_date' => $this->transformExcelDate($value),
                'as_time' => $this->transformExcelTime($value)
            ]);
        }
    }

    /**
     * Transformar fecha Excel (mejorado)
     */
    private function transformExcelDate($value)
    {
        if (empty($value)) {
            return null;
        }

        $value = trim($value);
        if (empty($value)) {
            return null;
        }

        try {
            // Verificar si es numérico (formato Excel)
            if (is_numeric($value)) {
                return Carbon::instance(Date::excelToDateTimeObject($value))->format('Y-m-d');
            }

            // Detecta fechas con patrones
            if (preg_match($this->datePatterns['dmy'], $value)) {
                try {
                    return Carbon::createFromFormat('d/m/y', $value)->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            }

            if (preg_match($this->datePatterns['dmY'], $value)) {
                try {
                    return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Error transformando fecha: " . $value . " - " . $e->getMessage());
            return null;
        }

        return null;
    }

    /**
     * Transformar hora Excel
     */
    private function transformExcelTime($value)
    {
        if (empty($value)) {
            return null;
        }

        $value = trim($value);
        if (empty($value)) {
            return null;
        }

        try {
            // Si es un decimal (formato Excel para tiempo)
            if (is_numeric($value)) {
                $seconds = floatval($value) * 86400; // Convertir a segundos
                $hours = floor($seconds / 3600);
                $minutes = floor(($seconds % 3600) / 60);
                $seconds = $seconds % 60;

                return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            }

            // Si ya es formato de hora
            if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $value)) {
                return $value;
            }

        } catch (\Exception $e) {
            Log::warning("Error transformando hora: " . $value . " - " . $e->getMessage());
            return null;
        }

        return null;
    }


    /**
     * Import CSV directly using MySQL LOAD DATA INFILE (fastest method)
     */
    private function importCsvDirectly($csvPath, $tableName, $batchId, $fileName, $fechaHora)
    {
        $targetPlanillas = ['901469000', '902343052'];

        try {
            Log::info("Starting MySQL LOAD DATA INFILE import", [
                'csv_path' => $csvPath,
                'table' => $tableName
            ]);

            // Convert Windows path separators for MySQL
            $mysqlPath = str_replace('\\', '/', $csvPath);

            // Build the LOAD DATA INFILE query - ACTUALIZADOS los nombres de columnas
            $sql = "
            LOAD DATA LOCAL INFILE '{$mysqlPath}'
            INTO TABLE {$tableName}
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\\n'
            IGNORE 1 LINES
            (cod, deposito_origen, cod_destino, deposito_destino, planilla, flete, nombre_fletero,
             camion, patente, fecha_salida_raw, hora_salida_raw, fecha_llegada_raw, hora_llegada_raw, diferencia_horas,
             distancia, categoria_flete, cierre, status, puntaje, tarifa, cod_producto, producto,
             salida, entrada, valor_producto, variedad, linea, tipo, numero_orden, fecha_orden_raw)
            SET
                batch_id = '{$batchId}',
                file_name = '{$fileName}',
                fecha_registro = '{$fechaHora}',
                final_status = '1',
                created_at = NOW(),
                updated_at = NOW()
        ";

            // Execute the LOAD DATA INFILE
            $result = DB::connection()->getPdo()->exec($sql);

            Log::info("MySQL LOAD DATA INFILE completed", [
                'affected_rows' => $result
            ]);

            // Count total records imported
            $totalImported = DB::table($tableName)->count();

            // Count target planillas
            $targetFound = DB::table($tableName)
                ->whereIn('planilla', $targetPlanillas)
                ->count();

            Log::info("CSV import completed successfully", [
                'total_imported' => $totalImported,
                'target_planillas_found' => $targetFound,
                'target_planillas' => $targetPlanillas
            ]);

            return $totalImported;

        } catch (\Exception $e) {
            Log::error("Error with MySQL LOAD DATA INFILE, falling back to PHP method: ".$e->getMessage());

            // Fallback to PHP method if LOAD DATA INFILE fails
            return $this->importCsvWithPhpFallback($csvPath, $tableName, $batchId, $fileName, $fechaHora);
        }
    }

    /**
     * Fallback PHP method with progress logging
     */
    private function importCsvWithPhpFallback($csvPath, $tableName, $batchId, $fileName, $fechaHora)
    {
        $targetPlanillas = ['901469000', '902343052'];

        try {
            $handle = fopen($csvPath, 'r');
            if ($handle === false) {
                throw new \Exception("Cannot open file: $csvPath");
            }

            // Read headers
            $headers = fgetcsv($handle);
            if ($headers === false) {
                throw new \Exception("Cannot read headers from CSV");
            }

            Log::info("Starting PHP fallback import", [
                'headers_count' => count($headers)
            ]);

            // Expected columns mapping
            $columnMapping = [
                'cod_ori' => 'cod',
                'deposito_origen' => 'deposito_origen',
                'cod_des' => 'cod_destino',
                'deposito_destino' => 'deposito_destino',
                'planilla' => 'planilla',
                'flete' => 'flete',
                'nombre_fletero' => 'nombre_fletero',
                'cam' => 'camion',
                'patente' => 'patente',
                'fecha_salida' => 'fecha_salida_raw',
                'hora_salida' => 'hora_salida_raw',
                'fecha_entrada' => 'fecha_llegada_raw',
                'hora_entrada' => 'hora_llegada_raw',
                'fecha_orden' => 'fecha_orden_raw',
                'diferencia_en_horas' => 'diferencia_horas',
                'dist' => 'distancia',
                'cat_flete' => 'categoria_flete',
                'cierre' => 'cierre',
                'status' => 'status',
                'ptaje_paleta' => 'puntaje',
                'tarif_adic' => 'tarifa',
                'cod_prod' => 'cod_producto',
                'producto' => 'producto',
                'sal' => 'salida',
                'ent' => 'entrada',
                'valor_por_producto' => 'valor_producto',
                'variedad' => 'variedad',
                'linea' => 'linea',
                'tip_ord' => 'tipo',
                'numero_orden' => 'numero_orden',
            ];

            DB::beginTransaction();

            $batchSize = 5000; // Even larger batches
            $batch = [];
            $totalImported = 0;
            $targetFound = 0;
            $rowsProcessed = 0;

            while (($row = fgetcsv($handle)) !== false) {
                $rowsProcessed++;

                // Log progress every 5000 rows
                if ($rowsProcessed % 5000 === 0) {
                    Log::info("Processing progress", [
                        'rows_processed' => $rowsProcessed,
                        'batches_imported' => floor($totalImported / $batchSize)
                    ]);
                }

                // Extend row to match headers count (fill missing columns with null)
                while (count($row) < count($headers)) {
                    $row[] = '';
                }

                // Truncate row if it has more columns than headers
                $row = array_slice($row, 0, count($headers));

                // Combine headers with values
                $data = array_combine($headers, $row);

                // Count target planillas (no logging for speed)
                $planillaValue = trim($data['planilla'] ?? '');
                if (in_array($planillaValue, $targetPlanillas)) {
                    $targetFound++;
                }

                // Create record with mapped columns
                $record = [];
                foreach ($columnMapping as $csvHeader => $dbColumn) {
                    $value = $data[$csvHeader] ?? '';
                    $record[$dbColumn] = trim($value) === '' ? null : trim($value);
                }

                // Add metadata
                $record['fecha_salida'] = $this->transformExcelDate($record['fecha_salida_raw']);
                $record['hora_salida'] = $this->transformExcelTime($record['hora_salida_raw']);
                $record['fecha_llegada'] = $this->transformExcelDate($record['fecha_llegada_raw']);
                $record['hora_llegada'] = $this->transformExcelTime($record['hora_llegada_raw']);
                $record['fecha_orden'] = $this->transformExcelDate($record['fecha_orden_raw']);
                $record['batch_id'] = $batchId;
                $record['file_name'] = $fileName;
                $record['fecha_registro'] = $fechaHora;
                $record['final_status'] = '1';
                $record['created_at'] = now();
                $record['updated_at'] = now();

                $batch[] = $record;

                // Insert batch when it reaches the batch size
                if (count($batch) >= $batchSize) {
                    DB::table($tableName)->insert($batch);
                    $totalImported += count($batch);

                    Log::info("Batch inserted", [
                        'batch_size' => count($batch),
                        'total_imported' => $totalImported,
                        'target_found_so_far' => $targetFound
                    ]);

                    $batch = [];
                }
            }

            // Insert any remaining records
            if (count($batch) > 0) {
                DB::table($tableName)->insert($batch);
                $totalImported += count($batch);

                Log::info("Final batch inserted", [
                    'final_batch_size' => count($batch),
                    'total_imported' => $totalImported,
                    'target_found_total' => $targetFound
                ]);
            }

            fclose($handle);
            DB::commit();

            Log::info("PHP fallback import completed successfully", [
                'total_imported' => $totalImported,
                'target_planillas_found' => $targetFound,
                'rows_processed' => $rowsProcessed
            ]);

            return $totalImported;

        } catch (\Exception $e) {
            Log::error("Error in PHP fallback import: ".$e->getMessage(), [
                'file' => $fileName,
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }

            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $e;
        }
    }

    /**
     * Create temporary table with STRING columns for dates initially
     */
    private function createTempTableWithStringDates($tempTableName)
    {
        $sql = "CREATE TEMPORARY TABLE {$tempTableName} (
        cod VARCHAR(255),
        deposito_origen VARCHAR(255),
        cod_destino VARCHAR(255),
        deposito_destino VARCHAR(255),
        planilla VARCHAR(255),
        flete VARCHAR(255),
        nombre_fletero VARCHAR(255),
        camion VARCHAR(255),
        patente VARCHAR(255),
        fecha_salida_raw VARCHAR(255),
        hora_salida_raw VARCHAR(255),
        fecha_llegada_raw VARCHAR(255),
        hora_llegada_raw VARCHAR(255),
        fecha_salida DATE NULL,
        hora_salida TIME NULL,
        fecha_llegada DATE NULL,
        hora_llegada TIME NULL,
        diferencia_horas VARCHAR(255),
        distancia DECIMAL(10,2),
        categoria_flete VARCHAR(255),
        cierre VARCHAR(255),
        status VARCHAR(255),
        puntaje DECIMAL(10,2),
        tarifa DECIMAL(10,2),
        cod_producto VARCHAR(255),
        producto VARCHAR(255),
        salida DECIMAL(10,2),
        entrada DECIMAL(10,2),
        valor_producto DECIMAL(10,2),
        variedad VARCHAR(255),
        linea VARCHAR(255),
        tipo VARCHAR(255),
        numero_orden VARCHAR(255),
        fecha_orden_raw VARCHAR(255),
        fecha_orden DATE NULL,
        batch_id VARCHAR(255),
        file_name VARCHAR(255),
        fecha_registro DATETIME,
        final_status VARCHAR(10),
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        INDEX idx_planilla_patente_producto (planilla, patente, cod_producto),
        INDEX idx_batch (batch_id)
    )";

        DB::statement($sql);

        Log::info("Created temporary table with string date columns", [
            'table' => $tempTableName
        ]);
    }

    /**
     * Preprocess file to handle dual-row headers
     */
    private function preprocessFileHeaders($filePath)
    {
        $absolutePath = storage_path('app/'.$filePath);

        if (!file_exists($absolutePath)) {
            throw new \Exception("File not found: {$absolutePath}");
        }

        // Detect file extension
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        // If it's Excel, convert to CSV first, then process headers
        if (in_array($extension, ['xlsx', 'xls'])) {
            $csvPath = $this->convertExcelToCSV($absolutePath);
            $processedPath = $this->processCSVHeaders($csvPath);

            // Clean up intermediate CSV if different from final processed file
            if ($csvPath !== $processedPath && file_exists($csvPath)) {
                unlink($csvPath);
            }

            return $processedPath;
        } else {
            // Process CSV headers directly
            return $this->processCSVHeaders($absolutePath);
        }
    }

    /**
     * Convert Excel file to CSV
     */
    private function convertExcelToCSV($excelPath)
    {
        try {
            // Create temporary CSV file
            $csvPath = tempnam(sys_get_temp_dir(), 'excel_to_csv').'.csv';

            // Use PhpSpreadsheet to convert
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($excelPath);

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
            $writer->setDelimiter(',');
            $writer->setEnclosure('"');
            $writer->setLineEnding("\n");
            $writer->save($csvPath);

            // Clean up memory
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            Log::info("Converted Excel to CSV", [
                'excel_path' => $excelPath,
                'csv_path' => $csvPath
            ]);

            return $csvPath;

        } catch (\Exception $e) {
            Log::error("Error converting Excel to CSV: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Process CSV file to combine dual-row headers into single row
     */
    private function processCSVHeaders($csvPath)
    {
        try {
            // Open original file
            $handle = fopen($csvPath, 'r');
            if ($handle === false) {
                throw new \Exception("Cannot open CSV file: {$csvPath}");
            }

            // Read first two rows (headers)
            $firstRow = fgetcsv($handle, 0, ',');
            $secondRow = fgetcsv($handle, 0, ',');

            if (!$firstRow || !$secondRow) {
                fclose($handle);
                throw new \Exception("Cannot read dual headers from CSV file");
            }

            // Combine and clean headers
            $combinedHeaders = [];
            foreach ($firstRow as $index => $header1) {
                $header2 = $secondRow[$index] ?? '';
                $combined = trim(($header1 ?? '').' '.$header2);
                $cleaned = $this->cleanHeaderName($combined);
                $combinedHeaders[] = $cleaned;
            }

            // Create temporary file with processed headers
            $tempFile = tempnam(sys_get_temp_dir(), 'processed_csv').'.csv';
            $tempHandle = fopen($tempFile, 'w');

            // Write combined headers
            fputcsv($tempHandle, $combinedHeaders);

            // Copy remaining data rows and check for target planillas
            $targetPlanillas = ['901469000', '902343052'];
            $rowCount = 0;
            $targetPlanillasFound = [];

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                $rowCount++;

                // Check if this row contains our target planillas
                // Assuming planilla is in first few columns, check first 5 columns
                for ($i = 0; $i < min(5, count($row)); $i++) {
                    if (in_array(trim($row[$i]), $targetPlanillas)) {
                        $targetPlanillasFound[] = [
                            'planilla' => trim($row[$i]),
                            'row_number' => $rowCount,
                            'full_row' => $row
                        ];
                        break;
                    }
                }

                fputcsv($tempHandle, $row);
            }

            // Close files
            fclose($handle);
            fclose($tempHandle);

            Log::info("CSV preprocessing completed", [
                'original_file' => $csvPath,
                'processed_file' => $tempFile,
                'headers_count' => count($combinedHeaders),
                'total_data_rows' => $rowCount,
                'target_planillas_found_in_csv' => count($targetPlanillasFound)
            ]);

            return $tempFile;

        } catch (\Exception $e) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            if (isset($tempHandle) && is_resource($tempHandle)) {
                fclose($tempHandle);
            }

            Log::error("Error processing CSV headers: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Clean and normalize header names
     */
    private function cleanHeaderName($header)
    {
        // Remove extra spaces and convert to lowercase
        $cleaned = strtolower(trim($header));

        // Replace non-alphanumeric characters with underscores
        $cleaned = preg_replace('/[^a-z0-9]+/', '_', $cleaned);

        // Remove leading/trailing underscores
        $cleaned = trim($cleaned, '_');

        // Handle empty headers
        if (empty($cleaned)) {
            $cleaned = 'column_'.uniqid();
        }

        return $cleaned;
    }

    /**
     * Process data from temp table - ultra-optimized version
     */
    private function processDataWithHistoryORM($tempTableName, $batchId)
    {
        $targetPlanillas = ['901469000', '902343052'];

        // Get total count and specific planillas info
        $totalRecords = DB::table($tempTableName)->count();
        $targetRecordsCount = DB::table($tempTableName)
            ->whereIn('planilla', $targetPlanillas)
            ->count();

        Log::info("Processing records from temp table", [
            'total_records' => $totalRecords,
            'target_planillas_found' => $targetRecordsCount
        ]);

        if ($totalRecords === 0) {
            Log::warning("No records found in temporary table");
            return [
                'total_processed' => 0,
                'updated' => 0,
                'inserted' => 0,
                'historical_records_created' => 0
            ];
        }

        // Ultra-fast bulk processing using raw SQL
        Log::info("Starting bulk processing with raw SQL");

        try {
            DB::beginTransaction();

            // Step 1: Insert all new records that don't exist (bulk insert)
            $insertSql = "
                INSERT INTO trucks (
                    cod, deposito_origen, cod_destino, deposito_destino, planilla, flete, nombre_fletero,
                    camion, patente, fecha_salida, hora_salida, fecha_llegada, hora_llegada, diferencia_horas,
                    distancia, categoria_flete, cierre, status, puntaje, tarifa, cod_producto, producto,
                    salida, entrada, valor_producto, variedad, linea, tipo, numero_orden, fecha_orden,
                    batch_id, file_name, fecha_registro, final_status, created_at, updated_at
                )
                SELECT
                    t.cod, t.deposito_origen, t.cod_destino, t.deposito_destino, t.planilla, t.flete, t.nombre_fletero,
                    t.camion, t.patente, t.fecha_salida, t.hora_salida, t.fecha_llegada, t.hora_llegada, t.diferencia_horas,
                    t.distancia, t.categoria_flete, t.cierre, t.status, t.puntaje, t.tarifa, t.cod_producto, t.producto,
                    t.salida, t.entrada, t.valor_producto, t.variedad, t.linea, t.tipo, t.numero_orden, t.fecha_orden,
                    t.batch_id, t.file_name, t.fecha_registro, t.final_status, t.created_at, t.updated_at
                FROM {$tempTableName} t
                LEFT JOIN trucks tr ON (
                    t.planilla = tr.planilla
                    AND t.patente = tr.patente
                    AND COALESCE(t.cod_producto, '') = COALESCE(tr.cod_producto, '')
                )
                WHERE tr.id IS NULL
            ";

            $insertedCount = DB::statement($insertSql) ? DB::getPdo()->lastInsertId() : 0;
            $insertedCount = DB::select("SELECT ROW_COUNT() as count")[0]->count ?? 0;

            Log::info("Bulk insert completed", [
                'inserted_count' => $insertedCount
            ]);

            // Step 2: Create historical records for existing records that will be updated
            $historySql = "
                INSERT INTO truck_histories (
                    planilla, patente, cod_producto, fecha_salida, batch_id,
                    original_data, change_type, changed_at, created_at, updated_at
                )
                SELECT
                    tr.planilla, tr.patente, tr.cod_producto, tr.fecha_salida, tr.batch_id,
                    JSON_OBJECT(
                        'id', tr.id, 'planilla', tr.planilla, 'patente', tr.patente,
                        'cod_producto', tr.cod_producto, 'status', tr.status
                    ),
                    'UPDATE',
                    NOW(),
                    NOW(),
                    NOW()
                FROM trucks tr
                INNER JOIN {$tempTableName} t ON (
                    t.planilla = tr.planilla
                    AND t.patente = tr.patente
                    AND COALESCE(t.cod_producto, '') = COALESCE(tr.cod_producto, '')
                )
            ";

            $historicalCount = DB::statement($historySql) ? DB::getPdo()->lastInsertId() : 0;
            $historicalCount = DB::select("SELECT ROW_COUNT() as count")[0]->count ?? 0;

            Log::info("Historical records created", [
                'historical_count' => $historicalCount
            ]);

            // Step 3: Update existing records (bulk update)
            $updateSql = "
                UPDATE trucks tr
                INNER JOIN {$tempTableName} t ON (
                    t.planilla = tr.planilla
                    AND t.patente = tr.patente
                    AND COALESCE(t.cod_producto, '') = COALESCE(tr.cod_producto, '')
                )
                SET
                    tr.cod = t.cod,
                    tr.deposito_origen = t.deposito_origen,
                    tr.cod_destino = t.cod_destino,
                    tr.deposito_destino = t.deposito_destino,
                    tr.flete = t.flete,
                    tr.nombre_fletero = t.nombre_fletero,
                    tr.camion = t.camion,
                    tr.fecha_salida = t.fecha_salida,
                    tr.hora_salida = t.hora_salida,
                    tr.fecha_llegada = t.fecha_llegada,
                    tr.hora_llegada = t.hora_llegada,
                    tr.diferencia_horas = t.diferencia_horas,
                    tr.distancia = t.distancia,
                    tr.categoria_flete = t.categoria_flete,
                    tr.cierre = t.cierre,
                    tr.status = t.status,
                    tr.puntaje = t.puntaje,
                    tr.tarifa = t.tarifa,
                    tr.producto = t.producto,
                    tr.salida = t.salida,
                    tr.entrada = t.entrada,
                    tr.valor_producto = t.valor_producto,
                    tr.variedad = t.variedad,
                    tr.linea = t.linea,
                    tr.tipo = t.tipo,
                    tr.numero_orden = t.numero_orden,
                    tr.fecha_orden = t.fecha_orden,
                    tr.batch_id = t.batch_id,
                    tr.file_name = t.file_name,
                    tr.fecha_registro = t.fecha_registro,
                    tr.final_status = t.final_status,
                    tr.updated_at = t.updated_at
            ";

            $updatedCount = DB::statement($updateSql) ? DB::getPdo()->lastInsertId() : 0;
            $updatedCount = DB::select("SELECT ROW_COUNT() as count")[0]->count ?? 0;

            Log::info("Bulk update completed", [
                'updated_count' => $updatedCount
            ]);

            DB::commit();

            // Final verification for target planillas
            $finalResults = [];
            foreach ($targetPlanillas as $planilla) {
                $finalCount = Truck::where('planilla', $planilla)->count();
                $finalResults[$planilla] = $finalCount;
            }

            Log::info("Bulk processing completed successfully", [
                'total_processed' => $totalRecords,
                'updated' => $updatedCount,
                'inserted' => $insertedCount,
                'historical_records' => $historicalCount,
                'target_planillas_final_count' => $finalResults
            ]);

            return [
                'total_processed' => $totalRecords,
                'updated' => $updatedCount,
                'inserted' => $insertedCount,
                'historical_records_created' => $historicalCount
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error in bulk processing: ".$e->getMessage());
            throw $e;
        }
    }
}
