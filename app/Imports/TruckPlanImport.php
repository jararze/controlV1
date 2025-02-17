<?php

namespace App\Imports;

use App\Models\Truck;
use App\Models\TruckHistory;
use Carbon\Carbon;
use Exception;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class TruckPlanImport implements ToModel, WithHeadingRow, WithStartRow, WithEvents, WithChunkReading, WithCustomCsvSettings
{

    protected $fileName;
    protected $batchId;
    protected $fechaHora;
    protected $filePath;

    public function __construct($fileName, $batchId, $fechaHora, $filePath)
    {
        $this->fileName = $fileName;
        $this->batchId = $batchId;
        $this->fechaHora = $fechaHora;
        $this->filePath = $filePath;
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
        return 100; // Process 1000 rows at a time
    }

    public function batchSize(): int
    {
        return 100;  // Process 100 records per batch
    }

    public function startRow(): int
    {
        return 3; // Inicia desde la fila 3
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                try {
                    Log::info('Starting import process for file: ' . $this->fileName);

                    // Safe check if we have a spreadsheet reader
                    if ($event->getReader() === null) {
                        Log::info('CSV import detected - using default headers');
                        return;
                    }

                    // Only try to access worksheet for Excel files
                    $extension = strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));
                    if (in_array($extension, ['xlsx', 'xls'])) {
                        $worksheet = $event->reader->getActiveSheet();

                        // Obtén las dos primeras filas
                        $firstRow = $worksheet->rangeToArray('A1:' . $worksheet->getHighestColumn() . '1')[0];
                        $secondRow = $worksheet->rangeToArray('A2:' . $worksheet->getHighestColumn() . '2')[0];

                        // Combina y transforma los encabezados
                        $cleanedHeaders = array_map(function ($header1, $header2) {
                            // Combina ambas filas
                            $combined = trim(($header1 ?? '') . ' ' . ($header2 ?? ''));

                            // Elimina caracteres especiales y reemplaza con _
                            $cleaned = preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($combined));

                            // Elimina guion bajo al final, si existe
                            return rtrim($cleaned, '_');
                        }, $firstRow, $secondRow);

                        // Sobrescribe las cabeceras transformadas en la primera fila
                        $worksheet->fromArray([$cleanedHeaders], null, 'A1');

                        // Elimina la segunda fila que ya no es necesaria
                        $worksheet->removeRow(2);

                        // Registra las cabeceras transformadas en los logs
                        Log::info('Cabeceras transformadas:', $cleanedHeaders);
                    }
                } catch (\Exception $e) {
                    Log::info('Processing as CSV file - ' . $e->getMessage());
                }
            },
        ];
    }

    /**
     * Combina las dos primeras filas en un único encabezado antes de la importación.
     */
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        try {
//            Log::info('Processing row: ', $row);

            $fechaSalida = $this->transformExcelDate($row['fecha_salida']);
            $fechaLlegada = $this->transformExcelDate($row['fecha_entrada']);
//            Log::info('Procesando fecha_salida:', [
//                'valor_original' => $row['fecha_salida'],
//                'transformado' => $this->transformExcelDate($row['fecha_salida']),
//            ]);

            $newData = [
                'cod' => $this->nullIfEmpty($row['cod_ori']),
                'deposito_origen' => $this->nullIfEmpty($row['deposito_origen']),
                'cod_destino' => $this->nullIfEmpty($row['cod_des']),
                'deposito_destino' => $this->nullIfEmpty($row['deposito_destino']),
                'planilla' => $this->nullIfEmpty($row['planilla']),
                'flete' => $this->nullIfEmpty($row['flete']),
                'nombre_fletero' => $this->nullIfEmpty($row['nombre_fletero']),
                'camion' => $this->nullIfEmpty($row['cam']),
//                'patente' => $this->nullIfEmpty($row['patente']),
                'patente' => $this->cleanPatente($row['patente']),
                'fecha_salida' => $fechaSalida,
                'hora_salida' => $this->nullIfEmpty($row['hora_salida']),
                'fecha_llegada' => $fechaLlegada,
                'hora_llegada' => $this->nullIfEmpty($row['hora_entrada']),
                'diferencia_horas' => $this->nullIfEmpty($row['diferencia_en_horas']),
                'distancia' => is_numeric($row['dist']) ? $row['dist'] : null,
                'categoria_flete' => $this->nullIfEmpty($row['cat_flete']),
                'cierre' => $this->nullIfEmpty($row['cierre']),
                'status' => $this->nullIfEmpty($row['status']),
                'puntaje' => is_numeric($row['ptaje_paleta']) ? $row['ptaje_paleta'] : null,
                'tarifa' => is_numeric($row['tarif_adic']) ? $row['tarif_adic'] : null,
                'cod_producto' => $this->nullIfEmpty($row['cod_prod']),
                'producto' => $this->nullIfEmpty($row['producto']),
                'salida' => is_numeric($row['sal']) ? $row['sal'] : null,
                'entrada' => is_numeric($row['ent']) ? $row['ent'] : null,
                'valor_producto' => is_numeric($row['valor_por_producto']) ? $row['valor_por_producto'] : null,
                'variedad' => $this->nullIfEmpty($row['variedad']),
                'linea' => $this->nullIfEmpty($row['linea']),
                'tipo' => $this->nullIfEmpty($row['tip_ord']),
                'numero_orden' => $this->nullIfEmpty($row['numero_orden']),
                'fecha_orden' => $this->transformExcelDate($row['fecha_orden']),
                'batch_id' => $this->batchId,
                'file_name' => $this->fileName,
                'fecha_registro' => $this->fechaHora,
                'final_status' => "1",
            ];

            // Buscar registro existente por planilla y patente
            $existingRecord = Truck::where('planilla', $newData['planilla'])
                ->where('patente', $newData['patente'])
                ->where('cod_producto', $newData['cod_producto'])
                ->first();

            if ($existingRecord) {
                // Comparar campos para detectar cambios
                $changes = array_diff_assoc($newData, $existingRecord->toArray());

                // Remover campos que no queremos comparar
                unset($changes['batch_id'], $changes['file_name'], $changes['fecha_registro']);

                if (!empty($changes)) {
                    // Guardar registro actual en histórico
                    TruckHistory::create([
                        'planilla' => $existingRecord->planilla,
                        'patente' => $existingRecord->patente,
                        'cod_producto' => $existingRecord->cod_producto,
                        'fecha_salida' => $existingRecord->fecha_salida,
                        'batch_id' => $existingRecord->batch_id,
                        'original_data' => $existingRecord->toArray(),
                        'change_type' => 'UPDATE',
                        'changed_at' => now(),
                    ]);

                    // Actualizar registro existente
                    $existingRecord->update($newData);
                    return null; // No crear nuevo registro
                }

                return null; // No hay cambios, no hacer nada
            }

            // Si no existe registro previo, crear uno nuevo
            return new Truck($newData);


        } catch (Exception $e) {
            Log::error("Error procesando fila: " . json_encode($row) . " - " . $e->getMessage());
            return null;
        }
    }

    private function nullIfEmpty($value)
    {
        // Elimina espacios adicionales y caracteres no imprimibles
        $value = trim($value);
        return empty($value) ? null : $value;
    }


    private function transformExcelDate($value)
    {

//        Log::info('Valor original de fecha_salida:', ['value' => $value]);
        $value = $this->nullIfEmpty($value); // Verifica si el valor está vacío

        if (is_null($value)) {
            return null;
        }

        // Limpia el valor eliminando espacios y caracteres no válidos
        $value = trim($value);

        // Verifica si el valor es un número (Formato serial de Excel)
        if (is_numeric($value)) {
            return Carbon::instance(Date::excelToDateTimeObject($value))->format('Y-m-d');
        }

        // Detecta fechas en formato DD/MM/YY o DD/MM/YYYY
        if (preg_match('/\d{1,2}\/\d{1,2}\/\d{2,4}/', $value)) {
            try {
                // Si el año tiene dos dígitos, usa 'd/m/y'
                if (preg_match('/\d{1,2}\/\d{1,2}\/\d{2}$/', $value)) {
                    return Carbon::createFromFormat('d/m/y', $value)->format('Y-m-d');
                }

                // Si el año tiene cuatro dígitos, usa 'd/m/Y'
                return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
            } catch (\Exception $e) {
                Log::error('Fecha inválida después de limpiar: ' . $value);
                return null;
            }
        }

        // Si el valor no es una fecha válida
        Log::error('Fecha inválida después de limpiar: ' . $value);
        return null;
    }


    private function cleanPatente($value)
    {
        if (empty($value)) {
            return null;
        }

        // Elimina espacios en blanco y guiones
        return str_replace([' ', '-'], '', trim($value));
    }


}
