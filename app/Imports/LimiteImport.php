<?php

namespace App\Imports;

use App\Models\Limite;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\AfterImport;
use Illuminate\Support\Facades\Log;

class LimiteImport implements ToModel, WithHeadingRow, WithEvents, WithBatchInserts, WithChunkReading
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

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        try {
            // Validaciones básicas
            if (empty($row['placa'])) {
                return null;
            }

            // Procesamiento de la fecha_alerta
            $fechaAlerta = null;
            if (!empty($row['fecha_alerta'])) {
                // Si es un valor numérico de Excel
                if (is_numeric($row['fecha_alerta'])) {
                    $fechaAlerta = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['fecha_alerta']));
                } else {
                    // Intentar parsear como string
                    try {
                        $fechaAlerta = Carbon::parse($row['fecha_alerta']);
                    } catch (\Exception $e) {
                        Log::warning("Error al parsear fecha: " . $e->getMessage());
                    }
                }
            }

            // Procesamiento de tiempos con normalización
            $tiempoMovimiento = $this->parseTime($row['tiempo_movimiento'] ?? null);
            $tiempoEncendido = $this->parseTime($row['tiempo_encendido'] ?? null);
            $tiempoRalenti = $this->parseTime($row['tiempo_ralenti'] ?? null);

            return new Limite([
                'PLACA' => $row['placa'] ?? null,
                'GRUPO' => $row['grupo'] ?? null,
                'DESCRIPCION' => $row['descripcion'] ?? null,
                'FECHA_ALERTA' => $fechaAlerta,
                'TIEMPO_MOVIMIENTO' => $tiempoMovimiento,
                'UBICACION' => $row['ubicacion'] ?? null,
                'DIRECCION' => $row['direccion'] ?? null,
                'TIEMPO_ENCENDIDO' => $tiempoEncendido,
                'TIEMPO_RALENTI' => $tiempoRalenti,
                'batch_id' => $this->batchId,
                'file_name' => $this->fileName,
                'fecha_registro' => $this->fechaHora,
                'final_status' => "1"
            ]);
        } catch (\Exception $e) {
            Log::error("Error procesando fila: " . json_encode($row) . " - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parseador de tiempos en formato HH:MM:SS
     */
    private function parseTime($value)
    {
        if (empty($value)) {
            return null;
        }

        // Si es numérico (formato Excel)
        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('H:i:s');
        }

        // Si ya es string en formato de tiempo, asegurarse que sea válido
        if (is_string($value) && preg_match('/^\d{1,2}:\d{1,2}(:\d{1,2})?$/', $value)) {
            // Dividir en componentes
            $parts = explode(':', $value);
            $hours = (int)$parts[0];
            $minutes = (int)$parts[1];
            $seconds = isset($parts[2]) ? (int)$parts[2] : 0;

            // Normalizar: si los minutos son 60 o más, ajustar las horas
            if ($minutes >= 60) {
                $hours += floor($minutes / 60);
                $minutes = $minutes % 60;
            }

            // Normalizar segundos si es necesario
            if ($seconds >= 60) {
                $minutes += floor($seconds / 60);
                $seconds = $seconds % 60;

                // Volver a verificar minutos por si se ajustaron
                if ($minutes >= 60) {
                    $hours += floor($minutes / 60);
                    $minutes = $minutes % 60;
                }
            }

            // Formatear correctamente
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return null;
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function(BeforeImport $event) {
                ini_set('memory_limit', '512M');
                set_time_limit(3600);
                DB::statement('SET SESSION wait_timeout = 28800');
                Log::info('Iniciando importación de Limite', ['file' => $this->fileName]);
            },
            AfterImport::class => function(AfterImport $event) {
                Log::info('Importación de Limite completada', ['file' => $this->fileName]);
                gc_collect_cycles();
            }
        ];
    }
}
