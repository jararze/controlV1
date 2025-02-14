<?php

namespace App\Imports;

use App\Models\MatrixHistory;
use App\Models\Uploads\Matrix;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;


class MatrixPlanImport implements ToModel, WithMultipleSheets, WithHeadingRow, WithCalculatedFormulas, WithChunkReading, WithBatchInserts
{
    protected $fileName;
    protected $batchId;
    protected $fecha_hora;
    protected $rowCount = 0;
    protected $errorCount = 0;
    protected $errors = [];

    protected $successCount = 0;
    protected $failedRows = [];
    protected $currentChunk = 0;

    public function __construct($fileName, $batchId, $fecha_hora)
    {

        $this->fileName = $fileName;
        $this->batchId = $batchId;
        $this->fecha_hora = $fecha_hora;

    }

    public function sheets(): array
    {
        return [
            "Base" => $this, // Indica que solo la hoja "BASE" será importada
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function batchSize(): int
    {
        return 100;
    }

    protected function validateRow(array $row)
    {
        $errors = [];

        // Validaciones específicas para cada campo requerido
        $requiredFields = [
            'planilla' => 'Planilla',
            'cod_prod' => 'Código de Producto',
            'patente' => 'Patente'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($row[$field])) {
                $errors[] = "Campo {$label} está vacío";
            }
        }

        // Validación de campos de fecha que DEBEN ser fechas
        $strictDateFields = ['salida', 'ultimo_reporte_gps'];
        foreach ($strictDateFields as $field) {
            if (isset($row[$field]) && !empty($row[$field])) {

                if (in_array($row[$field], ['#N/A', '#REF!', 'null', '#N/D', '#¡REF!', '#¡VALOR!', '#VALUE!'])) {
                    continue;
                }

                $transformedDate = $this->transformExcelDate($row[$field], $field);
                if ($transformedDate === null) {
                    $errors[] = sprintf(
                        "Formato de fecha inválido en campo %s. Valor: '%s' (tipo: %s)",
                        $field,
                        is_string($row[$field]) ? $row[$field] : gettype($row[$field]),
                        gettype($row[$field])
                    );
                }
            }
        }

        return $errors;
    }

    protected function isValidDate($value)
    {
        if (empty($value) || $value === '#N/A' || $value === '#REF!' || $value === 'null') {
            return true;
        }

        try {
            if (is_numeric($value)) {
                Date::excelToDateTimeObject($value);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $this->rowCount++;

        try {
            $validationErrors = $this->validateRow($row);
            if (!empty($validationErrors)) {
                $this->logRowError(new \Exception(implode(', ', $validationErrors)), $row);
                return null;
            }

            $specialValues = ['#N/A', '#REF!', 'null', '#N/D', '#¡REF!', '#¡VALOR!', '#VALUE!', 'N/A'];

            $modelData = [
                'cod_origen' => $row['cod_origen'] ?? null,
                'dep_origen' => $row['dep_origen'] ?? null,
                'cod_destino' => $row['cod_des'] ?? null,
                'dep_destino' => $row['dep_des'] ?? null,
                'planilla' => $row['planilla'] ?? null,
                'nombre_fletero' => $row['nombre_fletero'] ?? null,
                'cod_cam' => $row['cod_cam'] ?? null,
                'patente' => $row['patente'] ?? null,
//                'salida' => isset($row['salida']) ? $this->transformExcelDate($row['salida']) : null,
                'columna1' => $row['columna1'] ?? null,
                'status' => $row['status'] ?? null,
                'cod_prod' => $row['cod_prod'] ?? null,
                'producto' => $row['producto'] ?? null,
                'bultos' => $row['bultos'] ?? null,
                'tipo_producto' => $row['tipo_prod'] ?? null,
                'tipo_viaje' => $row['tipo_viaje'] ?? null,
                'hl' => $row['hl'] ?? null,
                'referencia' => $row['referencia'] ?? null,
                'eta' => $row['eta'] ?? null,
                'obs_eta' => $row['obs_eta'] ?? null,
                'placa_real' => $row['placa_real'] ?? null,
                'eta_observacion' => $row['eta_observacion'] ?? null,
                'comparacion_eta' => isset($row['comparacion_eta']) ? $this->transformExcelDate($row['comparacion_eta']) : null,
                'comparacion_obs_eta' => $row['comparacion_obs_eta'] ?? null,
                'gps' => $row['gps'] ?? null,
                'coordenadas' => $row['coordenadas'] ?? null,
//                'ultimo_reporte_gps' => isset($row['ultimo_reporte_gps']) ? $this->transformExcelDate($row['ultimo_reporte_gps']) : null,
                'ultimo_reporte_gps' => isset($row['ultimo_reporte_gps']) && !in_array($row['ultimo_reporte_gps'], $specialValues)
                    ? $this->transformExcelDate($row['ultimo_reporte_gps'])
                    : null,
                'salida' => isset($row['salida']) && !in_array($row['salida'], $specialValues)
                    ? $this->transformExcelDate($row['salida'])
                    : null,
                'ruta' => $row['ruta'] ?? null,
                'sla_dias' => $row['sla_dias'] ?? null,
                'tgt' => $row['tgt'] ?? null,
                'tmv_vs_sla' => $row['tmv_vs_sla'] ?? null,
                'duplicado' => $row['duplicado'] ?? null,
                'sku_agrupado' => $row['sku_agrupado'] ?? null,
                'marca' => $row['marca'] ?? null,
                'calibre' => $row['calibre'] ?? null,
                'clase' => $row['clase'] ?? null,
                'batch_id' => $this->batchId,
                'fecha_registro' => $this->fecha_hora,
                'file_name' => $this->fileName,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $existingMatrix = Matrix::where('planilla', $modelData['planilla'])
                ->where('patente', $modelData['patente'])
                ->where('cod_prod', $modelData['cod_prod'])
                ->first();

            if ($existingMatrix) {
                $changes = array_diff_assoc($modelData, $existingMatrix->toArray());

                // Remover campos que no queremos comparar
                unset($changes['batch_id'], $changes['file_name'], $changes['fecha_registro']);


                if (!empty($changes)) {
                    MatrixHistory::create([
                        'planilla' => $existingMatrix->planilla,
                        'patente' => $existingMatrix->patente,
                        'cod_prod' => $existingMatrix->cod_prod,
                        'salida' => $existingMatrix->salida,
                        'batch_id' => $existingMatrix->batch_id,
                        'original_data' => json_encode($existingMatrix->toArray()), // Convertir a JSON antes de guardar
                        'change_type' => 'UPDATE',
                        'changed_at' => now(),
                    ]);
                    $existingMatrix->update($modelData);
                    return null;
                }

                return null;
            }

            return new Matrix($modelData);

        } catch (\Exception $e) {
            $this->logRowError($e, $row);
            return null;
        }
    }

    protected function logRowError(\Exception $e, array $row)
    {
        $errorInfo = [
            'fila' => $this->rowCount,
            'planilla' => $row['planilla'] ?? 'N/A',
            'cod_prod' => $row['cod_prod'] ?? 'N/A',
            'patente' => $row['patente'] ?? 'N/A',
            'error' => $e->getMessage(),
            'detalles' => $e->getMessage()
        ];

        if (isset($row['ultimo_reporte_gps'])) {
            $errorInfo['ultimo_reporte_gps'] = [
                'valor' => $row['ultimo_reporte_gps'],
                'tipo' => gettype($row['ultimo_reporte_gps'])
            ];
        }

        if (isset($row['salida'])) {
            $errorInfo['salida'] = [
                'valor' => $row['salida'],
                'tipo' => gettype($row['salida'])
            ];
        }

        if (isset($row['eta'])) {
            $errorInfo['eta'] = [
                'valor' => $row['eta'],
                'tipo' => gettype($row['eta'])
            ];
        }

        if (isset($row['eta_observacion'])) {
            $errorInfo['eta_observacion'] = [
                'valor' => $row['eta_observacion'],
                'tipo' => gettype($row['eta_observacion'])
            ];
        }

        Log::error("Error en importación", $errorInfo);
        $this->errors[] = $errorInfo;
    }

    protected function getDetailedErrorMessage(\Exception $e, array $row)
    {
        $message = $e->getMessage();

        // Si es un error de SQL, intentar extraer información más específica
        if (strpos($message, 'SQLSTATE') !== false) {
            preg_match('/Column (.*?) doesn\'t match/', $message, $matches);
            if (!empty($matches[1])) {
                return "Error en la columna: " . $matches[1];
            }
        }

        return $message;
    }

    private function transformExcelDate($value, $fieldName = '')
    {
        try {
            // Si el valor está vacío o es un valor especial, retornar null
            if (empty($value) || in_array($value, ['#N/A', '#REF!', 'null', '#N/D', '#¡REF!', '#¡VALOR!', '#VALUE!', 'N/A'])) {
                Log::info("Valor especial detectado, retornando null", [
                    'campo' => $fieldName,
                    'valor' => $value
                ]);
                return null;
            }

            if (is_string($value)) {
                // Formato YYYY/MM/DD HH:mm:ss
                if (preg_match('/^\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
                    return Carbon::createFromFormat('Y/m/d H:i:s', $value)->format('Y-m-d H:i:s');
                }
                // Formato YYYY-MM-DD HH:mm:ss
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
                    return $value; // Ya está en el formato correcto
                }
            }

            if (is_numeric($value)) {
                if ($value > 0) {
                    try {
                        $date = Date::excelToDateTimeObject($value);
                        return Carbon::instance($date)->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        Log::warning("Error convirtiendo número Excel a fecha", [
                            'campo' => $fieldName,
                            'valor' => $value,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                return null;
            }


            return null;

        } catch (\Exception $e) {
            Log::error("Error general transformando fecha", [
                'campo' => $fieldName,
                'valor' => $value,
                'tipo' => gettype($value),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    public function onError(Throwable $e)
    {
        Log::error("Error general en la importación", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}
