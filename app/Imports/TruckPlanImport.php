<?php

namespace App\Imports;

use App\Models\Truck;
use App\Models\TruckHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\Importable;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\DefaultValueBinder;

class TruckPlanImport implements ToModel, WithHeadingRow, WithStartRow, WithEvents, WithChunkReading, WithCustomCsvSettings, WithBatchInserts
{

    use Importable;
    protected $fileName;
    protected $batchId;
    protected $fechaHora;
    protected $filePath;

    // Cache para patentes ya procesadas
    protected $processedRegistries = [];

    // Formato de fechas comunes precalculadas
    protected $datePatterns = [
        'dmy' => '/\d{1,2}\/\d{1,2}\/\d{2}$/',
        'dmY' => '/\d{1,2}\/\d{1,2}\/\d{4}$/',
    ];

    protected $onChunkReadCallback = null;
    protected $processedRows = 0;

    public function __construct($fileName, $batchId, $fechaHora, $filePath)
    {
        $this->fileName = $fileName;
        $this->batchId = $batchId;
        $this->fechaHora = $fechaHora;
        $this->filePath = $filePath;
    }

    /**
     * @param Cell $cell
     * @param mixed $value
     * @return bool
     */


    public function onChunkRead(callable $callback)
    {
        $this->onChunkReadCallback = $callback;
        return $this;
    }

    /**
     * This method is called after each chunk is read
     *
     * @param array $chunk
     */
    public function chunkRead(array $chunk)
    {
        // Aumentar contador de filas procesadas
        $this->processedRows += count($chunk);

        // Llamar al callback si está definido
        if (is_callable($this->onChunkReadCallback)) {
            call_user_func($this->onChunkReadCallback, $this->processedRows);
        }
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
            'enclosure' => '"',
            'escape_character' => '\\',
            'contiguous' => false,
            'input_encoding' => 'UTF-8',
        ];
    }

    public function chunkSize(): int
    {
        return 500; // Process 1000 rows at a time
    }

    public function batchSize(): int
    {
        return 500;  // Process 100 records per batch
    }

    public function startRow(): int
    {
        return 2; // Inicia desde la fila 3
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                try {
                    Log::info('Starting import process for file: ' . $this->fileName);

                    // Solo para archivos Excel, los CSV se procesan aparte
                    $extension = strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));
                    if (in_array($extension, ['xlsx', 'xls']) && $event->getReader() !== null) {
                        $worksheet = $event->reader->getActiveSheet();

                        // Procesar cabeceras en Excel
                        $firstRow = $worksheet->rangeToArray('A1:' . $worksheet->getHighestColumn() . '1')[0];
                        $secondRow = $worksheet->rangeToArray('A2:' . $worksheet->getHighestColumn() . '2')[0];

                        $cleanedHeaders = [];
                        foreach ($firstRow as $index => $header1) {
                            $header2 = $secondRow[$index] ?? '';
                            $combined = trim(($header1 ?? '') . ' ' . $header2);
                            $cleanedHeaders[] = rtrim(preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($combined)), '_');
                        }

                        // Sobrescribir cabeceras
                        $worksheet->fromArray([$cleanedHeaders], null, 'A1');
                        $worksheet->removeRow(2);
                    }
                } catch (\Exception $e) {
                    Log::warning('Error procesando cabeceras Excel: ' . $e->getMessage());
                }
            },
        ];
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            // Validación rápida para filas vacías
            if (empty($row['planilla']) || empty($row['patente'])) {
                return null;
            }

            // Limpieza de patente (reutilizable)
            $patente = $this->cleanPatente($row['patente']);
            if (empty($patente)) {
                return null;
            }

            // Transformación de fechas optimizada
            $fechaSalida = $this->transformExcelDate($row['fecha_salida']);
            $fechaLlegada = $this->transformExcelDate($row['fecha_entrada']);

            // Clave única para esta combinación
            $uniqueKey = $row['planilla'] . '_' . $patente . '_' . ($row['cod_prod'] ?? '');

            // Si ya procesamos este registro en este batch, omitirlo
            if (isset($this->processedRegistries[$uniqueKey])) {
                return null;
            }

            // Marcar como procesado
            $this->processedRegistries[$uniqueKey] = true;

            // Preparación eficiente de datos
            $newData = [
                'cod' => $this->nullIfEmpty($row['cod_ori']),
                'deposito_origen' => $this->nullIfEmpty($row['deposito_origen']),
                'cod_destino' => $this->nullIfEmpty($row['cod_des']),
                'deposito_destino' => $this->nullIfEmpty($row['deposito_destino']),
                'planilla' => $this->nullIfEmpty($row['planilla']),
                'flete' => $this->nullIfEmpty($row['flete']),
                'nombre_fletero' => $this->nullIfEmpty($row['nombre_fletero']),
                'camion' => $this->nullIfEmpty($row['cam']),
                'patente' => $patente,
                'fecha_salida' => $fechaSalida,
                'hora_salida' => $this->nullIfEmpty($row['hora_salida']),
                'fecha_llegada' => $fechaLlegada,
                'hora_llegada' => $this->nullIfEmpty($row['hora_entrada']),
                'diferencia_horas' => $this->nullIfEmpty($row['diferencia_en_horas']),
                'distancia' => is_numeric($row['dist'] ?? null) ? $row['dist'] : null,
                'categoria_flete' => $this->nullIfEmpty($row['cat_flete']),
                'cierre' => $this->nullIfEmpty($row['cierre']),
                'status' => $this->nullIfEmpty($row['status']),
                'puntaje' => is_numeric($row['ptaje_paleta'] ?? null) ? $row['ptaje_paleta'] : null,
                'tarifa' => is_numeric($row['tarif_adic'] ?? null) ? $row['tarif_adic'] : null,
                'cod_producto' => $this->nullIfEmpty($row['cod_prod']),
                'producto' => $this->nullIfEmpty($row['producto']),
                'salida' => is_numeric($row['sal'] ?? null) ? $row['sal'] : null,
                'entrada' => is_numeric($row['ent'] ?? null) ? $row['ent'] : null,
                'valor_producto' => is_numeric($row['valor_por_producto'] ?? null) ? $row['valor_por_producto'] : null,
                'variedad' => $this->nullIfEmpty($row['variedad']),
                'linea' => $this->nullIfEmpty($row['linea']),
                'tipo' => $this->nullIfEmpty($row['tip_ord']),
                'numero_orden' => $this->nullIfEmpty($row['numero_orden']),
                'fecha_orden' => $this->transformExcelDate($row['fecha_orden'] ?? null),
                'batch_id' => $this->batchId,
                'file_name' => $this->fileName,
                'fecha_registro' => $this->fechaHora,
                'final_status' => "1",
            ];

            // Búsqueda optimizada - consulta directa en lugar de usar Eloquent
            $existingRecord = DB::table('trucks')
                ->where('planilla', $newData['planilla'])
                ->where('patente', $newData['patente'])
                ->where('cod_producto', $newData['cod_producto'] ?? '')
                ->first();

            if ($existingRecord) {
                // Convertir a array para comparación
                $existingArray = (array) $existingRecord;

                // Comparar campos para detectar cambios - optimizado
                $changes = [];
                foreach ($newData as $key => $value) {
                    if (isset($existingArray[$key]) && $existingArray[$key] != $value &&
                        !in_array($key, ['batch_id', 'file_name', 'fecha_registro'])) {
                        $changes[$key] = $value;
                    }
                }

                if (!empty($changes)) {
                    // Guardar histórico de forma optimizada
                    TruckHistory::create([
                        'planilla' => $existingArray['planilla'],
                        'patente' => $existingArray['patente'],
                        'cod_producto' => $existingArray['cod_producto'],
                        'fecha_salida' => $existingArray['fecha_salida'],
                        'batch_id' => $existingArray['batch_id'],
                        'original_data' => json_encode($existingArray),
                        'change_type' => 'UPDATE',
                        'changed_at' => now(),
                    ]);

                    // Actualizar con consulta directa por rendimiento
                    DB::table('trucks')
                        ->where('id', $existingArray['id'])
                        ->update($newData);
                }

                return null;
            }

            // Si no existe registro previo, crear uno nuevo
            return new Truck($newData);
        } catch (Exception $e) {
            Log::error("Error procesando fila: " . json_encode($row) . " - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Optimizado para rendimiento
     */
    private function nullIfEmpty($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        return trim($value) ?: null;
    }

    /**
     * Transformación de fechas optimizada con caché de patrones
     */
    private function transformExcelDate($value)
    {
        if (empty($value)) {
            return null;
        }

        // Limpia el valor
        $value = trim($value);
        if (empty($value)) {
            return null;
        }

        // Verificar si es numérico (formato Excel)
        if (is_numeric($value)) {
            return Carbon::instance(Date::excelToDateTimeObject($value))->format('Y-m-d');
        }

        // Detecta fechas con patrones precalculados
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

        return null;
    }

    /**
     * Limpieza de patente optimizada
     */
    private function cleanPatente($value)
    {
        if (empty($value)) {
            return null;
        }

        return str_replace([' ', '-'], '', trim($value));
    }


}
