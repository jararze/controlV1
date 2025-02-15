<?php

namespace App\Imports;

use App\Models\Argus;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Str;

class ArgusPlanImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts, SkipsOnError
{
    use SkipsErrors;

    protected $fileName;
    protected $originalBatchId;
    protected $fechaHora;
    protected $batchesByDate = [];
    protected $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];

    public function __construct($fileName, $batchId, $fechaHora)
    {
        $this->fileName = $fileName;
        $this->originalBatchId = $batchId;
        $this->fechaHora = $fechaHora;
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * @return int
     */
    public function batchSize(): int
    {
        return 1000;
    }

    public function model(array $row)
    {
        try {
            $fechaOriginal = $row['dia'] ?? null;
            $horaOriginal = $row['hora_alarme'] ?? null;

            Log::info('Procesando fechas', [
                'fecha_original' => $fechaOriginal,
                'hora_original' => $horaOriginal,
                'row' => $row
            ]);

            if (!$fechaOriginal) {
                Log::warning('Fecha no encontrada', ['row' => $row]);
                return null;
            }

            // Convertir fecha y hora usando el método probado
            $dia = $this->transformExcelDate($fechaOriginal);
            $hora_alarma = $this->transformExcelDateWithTime($horaOriginal);

            Log::info('Fechas transformadas', [
                'fecha_original' => $fechaOriginal,
                'fecha_transformada' => $dia,
                'hora_original' => $horaOriginal,
                'hora_transformada' => $hora_alarma
            ]);

            if (!$dia) {
                Log::error('No se pudo transformar la fecha', [
                    'fecha_original' => $fechaOriginal,
                    'row' => $row
                ]);
                return null;
            }

            // Obtener la fecha base para el batch_id
            $carbonFecha = Carbon::parse($dia);
            $fechaBase = $carbonFecha->format('Y-m-d');

            // Generar batchId por fecha si no existe
            if (!isset($this->batchesByDate[$fechaBase])) {
                $this->batchesByDate[$fechaBase] = (string) Str::uuid();
                Log::info('Nuevo batch generado', [
                    'fecha_base' => $fechaBase,
                    'batch_id' => $this->batchesByDate[$fechaBase]
                ]);
            }

            // Generar nombre de archivo con fecha correcta
            $fileName = $carbonFecha->day . ' ' . $this->meses[$carbonFecha->month];

            return new Argus([
                'operacion' => $this->nullIfEmpty($row['operacao']),
                'patente' => $this->cleanPatente($row['frota']),
                'dia' => $dia,
                'evento' => $this->nullIfEmpty($row['evento']),
                'motorista' => $this->nullIfEmpty($row['motorista']),
                'hora_alarma' => $hora_alarma ?? $dia,
                'velocidade' => $this->nullIfEmpty($row['velocidade']),
                'latitude' => $this->transformCoordinate($row['latitude'] ?? $row['-18'] ?? 0),
                'longitude' => $this->transformCoordinate($row['longitude'] ?? 0),
                'event_id' => $this->nullIfEmpty($row['id']),
                'batch_id' => $this->batchesByDate[$fechaBase],
                'file_name' => $fileName,
                'fecha_registro' => $this->fechaHora,
                'final_status' => "1",
            ]);

        } catch (Exception $e) {
            Log::error("Error procesando fila en Argus Import", [
                'row' => $row,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function transformExcelDate($value)
    {
        $value = $this->nullIfEmpty($value);

        if (is_null($value)) {
            Log::warning('Valor de fecha nulo');
            return null;
        }

        $value = trim($value);

        try {
            // Si es un número (serial de Excel)
            if (is_numeric($value)) {
                $resultado = Carbon::instance(Date::excelToDateTimeObject($value))->format('Y-m-d');
                Log::info('Fecha transformada desde número Excel', [
                    'original' => $value,
                    'resultado' => $resultado
                ]);
                return $resultado;
            }

            // Para el formato Y-m-d (como 2025-02-14)
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
                $fecha = Carbon::createFromFormat('Y-m-d', $value);
                $resultado = $fecha->format('Y-m-d');
                Log::info('Fecha transformada desde ISO', [
                    'original' => $value,
                    'resultado' => $resultado
                ]);
                return $resultado;
            }

            // Para el formato m/d/Y o m/d/Y H:i
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $value, $matches)) {
                $mes = $matches[1];
                $dia = $matches[2];
                $anio = $matches[3];

                $fecha = Carbon::createFromFormat('Y-m-d', sprintf('%s-%02d-%02d', $anio, $mes, $dia));
                $resultado = $fecha->format('Y-m-d');
                Log::info('Fecha transformada desde formato con slash', [
                    'original' => $value,
                    'resultado' => $resultado
                ]);
                return $resultado;
            }

            Log::warning('Formato de fecha no reconocido', ['valor' => $value]);
            return null;

        } catch (\Exception $e) {
            Log::error('Error transformando fecha', [
                'valor' => $value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    private function transformExcelDateWithTime($value)
    {
        $value = $this->nullIfEmpty($value);

        if (is_null($value)) {
            return null;
        }

        $value = trim($value);
        try {
            // Si es un número (serial de Excel)
            if (is_numeric($value)) {
                $resultado = Carbon::instance(Date::excelToDateTimeObject($value))->format('Y-m-d H:i:s');
                Log::info('Fecha y hora transformada desde número Excel', [
                    'original' => $value,
                    'resultado' => $resultado
                ]);
                return $resultado;
            }

            // Para el formato Y-m-d H:i:s (2025-02-14 17:47:02)
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})$/', $value, $matches)) {
                $fecha = Carbon::createFromFormat('Y-m-d H:i:s', $value);
                $resultado = $fecha->format('Y-m-d H:i:s');
                Log::info('Fecha y hora transformada desde ISO completo', [
                    'original' => $value,
                    'resultado' => $resultado
                ]);
                return $resultado;
            }

            // Para el formato m/d/Y H:i
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})/', $value, $matches)) {
                $mes = $matches[1];
                $dia = $matches[2];
                $anio = $matches[3];
                $hora = $matches[4];
                $minuto = $matches[5];

                $fechaHora = Carbon::create($anio, $mes, $dia, $hora, $minuto, 0);
                $resultado = $fechaHora->format('Y-m-d H:i:s');
                Log::info('Fecha y hora transformada desde formato con slash', [
                    'original' => $value,
                    'resultado' => $resultado
                ]);
                return $resultado;
            }

            // Si solo tenemos la fecha, intentamos con transformExcelDate y agregamos 00:00:00
            $fecha = $this->transformExcelDate($value);
            if ($fecha) {
                $resultado = $fecha . ' 00:00:00';
                Log::info('Fecha transformada sin hora', [
                    'original' => $value,
                    'resultado' => $resultado
                ]);
                return $resultado;
            }

            Log::warning('Formato de fecha y hora no reconocido', ['valor' => $value]);
            return null;

        } catch (\Exception $e) {
            Log::error('Error transformando fecha con hora', [
                'valor' => $value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    private function nullIfEmpty($value): ?string
    {
        if (is_null($value)) {
            return null;
        }
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    private function transformCoordinate($value): string
    {
        if (empty($value)) {
            return "0";
        }
        return str_replace(',', '.', trim((string)$value));
    }

    private function cleanPatente($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        return preg_replace('/[^A-Za-z0-9]/', '', trim((string)$value));
    }
}
