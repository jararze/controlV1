<?php

namespace App\Imports;

use App\Models\Argus;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ArgusPlanImport implements ToModel, WithHeadingRow
{
    protected $fileName;
    protected $batchId;
    protected $fechaHora;

    public function __construct($fileName, $batchId, $fechaHora)
    {
        $this->fileName = $fileName;
        $this->batchId = $batchId;
        $this->fechaHora = $fechaHora;
    }

    /**
     * @param  array  $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
//        Log::info('Procesando fila: ', $row);
        try {

            $dia = $this->transformExcelDate($row['dia']);
            $hora_alarma = $this->transformExcelDate($row['hora_alarme']);

//            Log::info('Procesando alarma:', [
//                'valor_original' => $row['hora_alarme'],
//                'transformado' => $this->transformExcelDate($row['hora_alarme']),
//            ]);

            return new Argus([
                'operacion' => $this->nullIfEmpty($row['operacao']),
                'patente' => $this->cleanPatente($row['frota']),
                'dia' => $dia,
                'evento' => $this->nullIfEmpty($row['evento']),
                'motorista' => $this->nullIfEmpty($row['motorista']),
                'hora_alarma' => $hora_alarma,
                'velocidade' => $this->nullIfEmpty($row['velocidade']),
                'latitude' => str_replace(',', '.', $row['latitude']),
                'longitude' => str_replace(',', '.', $row['longitude']),
                'event_id' => $this->nullIfEmpty($row['id']),
                'batch_id' => $this->batchId,
                'file_name' => $this->fileName,
                'fecha_registro' => $this->fechaHora,
                'final_status' => "1",
            ]);
        } catch (Exception $e) {
            Log::error("Error procesando fila: ".json_encode($row)." - ".$e->getMessage());
        }
    }

    private function nullIfEmpty($value): ?string
    {
        // Elimina espacios adicionales y caracteres no imprimibles
        $value = trim($value);
        return empty($value) ? null : $value;
    }


    private function transformExcelDate($value): ?string
    {
        $value = $this->nullIfEmpty($value); // Verifica si el valor está vacío

        if (is_null($value)) {
            return null;
        }

        $value = trim($value);

        // Si es un número (serial de Excel)
        if (is_numeric($value)) {
            return Carbon::instance(Date::excelToDateTimeObject($value))->format('Y-m-d H:i:s');
        }

        // Maneja formatos con tiempo 'YYYY-MM-DD HH:MM:SS'
        if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $value)) {
            try {
                return Carbon::createFromFormat('Y-m-d H:i:s', $value)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                Log::error('Fecha y hora inválida después de limpiar: ' . $value);
                return null;
            }
        }

        // Maneja formatos solo de fecha 'YYYY-MM-DD'
        if (preg_match('/\d{4}-\d{2}-\d{2}/', $value)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
            } catch (\Exception $e) {
                Log::error('Fecha inválida después de limpiar: ' . $value);
                return null;
            }
        }

        // Maneja formatos 'd/m/Y' o 'd/m/y'
        if (preg_match('/\d{1,2}\/\d{1,2}\/\d{2,4}/', $value)) {
            try {
                if (preg_match('/\d{1,2}\/\d{1,2}\/\d{2}$/', $value)) {
                    return Carbon::createFromFormat('d/m/y', $value)->format('Y-m-d');
                }

                return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
            } catch (\Exception $e) {
                Log::error('Fecha inválida después de limpiar: ' . $value);
                return null;
            }
        }

        Log::error('Formato de fecha desconocido: ' . $value);
        return null;
    }


    private function cleanPatente($value): array|string|null
    {
        if (empty($value)) {
            return null;
        }
        return str_replace([' ', '-'], '', trim($value));
    }
}
