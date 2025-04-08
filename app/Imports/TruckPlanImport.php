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
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\Importable;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\DefaultValueBinder;

class TruckPlanImport implements ToModel, WithHeadingRow, WithStartRow, WithEvents, WithChunkReading, WithCustomCsvSettings, WithBatchInserts, ToArray
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

    protected $processedRows = 0;

    public function __construct($fileName, $batchId, $fechaHora, $filePath)
    {
        $this->fileName = $fileName;
        $this->batchId = $batchId;
        $this->fechaHora = $fechaHora;
        $this->filePath = $filePath;
    }

    /**
     * Implementación de ToArray para compatibilidad con Excel::toArray
     */
    public function array(array $array)
    {
        return $array;
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
        // Reducido para evitar problemas de memoria
        return 2000;
    }

    public function batchSize(): int
    {
        // Reducido para evitar sobrecargar la base de datos
        return 2000;
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
                    // Configurar límites de memoria y tiempo para la importación
                    ini_set('memory_limit', '1024M');
                    set_time_limit(7200); // 2 horas

                    // Configurar mayores timeouts de MySQL
                    DB::statement('SET SESSION wait_timeout = 28800');
                    DB::statement('SET SESSION interactive_timeout = 28800');

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

            // Añadir eventos para limpiar memoria
            AfterSheet::class => function(AfterSheet $event) {
                gc_collect_cycles();
            },

            AfterImport::class => function(AfterImport $event) {
                gc_collect_cycles();

                Log::info('Importación completada: ' . $this->fileName, [
                    'processed_rows' => $this->processedRows
                ]);
            }
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
            // Aumentar contador de filas procesadas
            $this->processedRows++;

            // Validación rápida para filas vacías
            if (empty($row['planilla']) || empty($row['patente'])) {
                return null;
            }

            // Limpieza de patente
            $patente = $this->cleanPatente($row['patente']);
            if (empty($patente)) {
                return null;
            }

            // Transformación de fechas
            $fechaSalida = $this->transformExcelDate($row['fecha_salida'] ?? null);
            $fechaLlegada = $this->transformExcelDate($row['fecha_entrada'] ?? null);
            $fechaOrden = $this->transformExcelDate($row['fecha_orden'] ?? null);

            // Clave única para esta combinación
            $uniqueKey = $row['planilla'] . '_' . $patente . '_' . ($row['cod_prod'] ?? '');

            // Si ya procesamos este registro en este batch, omitirlo
            if (isset($this->processedRegistries[$uniqueKey])) {
                return null;
            }

            // Marcar como procesado
            $this->processedRegistries[$uniqueKey] = true;

            // Usar transacción para cada registro
            DB::beginTransaction();
            try {
                $newData = [
                    'cod' => $this->nullIfEmpty($row['cod_ori'] ?? null),
                    'deposito_origen' => $this->nullIfEmpty($row['deposito_origen'] ?? null),
                    'cod_destino' => $this->nullIfEmpty($row['cod_des'] ?? null),
                    'deposito_destino' => $this->nullIfEmpty($row['deposito_destino'] ?? null),
                    'planilla' => $this->nullIfEmpty($row['planilla'] ?? null),
                    'flete' => $this->nullIfEmpty($row['flete'] ?? null),
                    'nombre_fletero' => $this->nullIfEmpty($row['nombre_fletero'] ?? null),
                    'camion' => $this->nullIfEmpty($row['cam'] ?? null),
                    'patente' => $patente,
                    'fecha_salida' => $fechaSalida,
                    'hora_salida' => $this->nullIfEmpty($row['hora_salida'] ?? null),
                    'fecha_llegada' => $fechaLlegada,
                    'hora_llegada' => $this->nullIfEmpty($row['hora_entrada'] ?? null),
                    'diferencia_horas' => $this->nullIfEmpty($row['diferencia_en_horas'] ?? null),
                    'distancia' => is_numeric($row['dist'] ?? null) ? $row['dist'] : null,
                    'categoria_flete' => $this->nullIfEmpty($row['cat_flete'] ?? null),
                    'cierre' => $this->nullIfEmpty($row['cierre'] ?? null),
                    'status' => $this->nullIfEmpty($row['status'] ?? null),
                    'puntaje' => is_numeric($row['ptaje_paleta'] ?? null) ? $row['ptaje_paleta'] : null,
                    'tarifa' => is_numeric($row['tarif_adic'] ?? null) ? $row['tarif_adic'] : null,
                    'cod_producto' => $this->nullIfEmpty($row['cod_prod'] ?? null),
                    'producto' => $this->nullIfEmpty($row['producto'] ?? null),
                    'salida' => is_numeric($row['sal'] ?? null) ? $row['sal'] : null,
                    'entrada' => is_numeric($row['ent'] ?? null) ? $row['ent'] : null,
                    'valor_producto' => is_numeric($row['valor_por_producto'] ?? null) ? $row['valor_por_producto'] : null,
                    'variedad' => $this->nullIfEmpty($row['variedad'] ?? null),
                    'linea' => $this->nullIfEmpty($row['linea'] ?? null),
                    'tipo' => $this->nullIfEmpty($row['tip_ord'] ?? null),
                    'numero_orden' => $this->nullIfEmpty($row['numero_orden'] ?? null),
                    'fecha_orden' => $fechaOrden,
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

                    DB::commit();
                    return null;
                }

                // Si no existe registro previo, crear uno nuevo
                $truck = new Truck($newData);
                $truck->save();

                DB::commit();
                return null;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error procesando registro: planilla=" . ($row['planilla'] ?? 'N/A') . ", patente=" . ($patente ?? 'N/A') . " - " . $e->getMessage());
                return null;
            }
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

        try {
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
        } catch (\Exception $e) {
            Log::warning("Error transformando fecha: " . $value . " - " . $e->getMessage());
            return null;
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

    public function __destruct()
    {
        // Liberar memoria al finalizar
        $this->processedRegistries = [];
        gc_collect_cycles();
    }
}
