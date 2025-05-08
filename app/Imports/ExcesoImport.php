<?php

namespace App\Imports;

use App\Models\Exceso;
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

class ExcesoImport implements ToModel, WithHeadingRow, WithEvents, WithBatchInserts, WithChunkReading
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
        return 50;
    }

    public function chunkSize(): int
    {
        return 25;
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

            // Procesamiento de fechas
            $fechaExceso = null;
            if (!empty($row['fecha_exceso'])) {
                if (is_numeric($row['fecha_exceso'])) {
                    $fechaExceso = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['fecha_exceso']));
                } else {
                    try {
                        $fechaExceso = Carbon::parse($row['fecha_exceso']);
                    } catch (\Exception $e) {
                        Log::warning("Error al parsear fecha exceso: " . $e->getMessage());
                    }
                }
            }

            $fechaRestitucion = null;
            if (!empty($row['fecha_restitucion'])) {
                if (is_numeric($row['fecha_restitucion'])) {
                    $fechaRestitucion = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['fecha_restitucion']));
                } else {
                    try {
                        $fechaRestitucion = Carbon::parse($row['fecha_restitucion']);
                    } catch (\Exception $e) {
                        Log::warning("Error al parsear fecha restitucion: " . $e->getMessage());
                    }
                }
            }

            return new Exceso([
                'PLACA' => $row['placa'] ?? null,
                'GRUPO' => $row['grupo'] ?? null,
                'DESCRIPCION' => $row['descripcion'] ?? null,
                'FECHA_EXCESO' => $fechaExceso,
                'FECHA_RESTITUCION' => $fechaRestitucion,
                'UBICACION' => $row['ubicacion'] ?? null,
                'DIRECCION' => $row['direccion'] ?? null,
                'DURACION_SEG' => is_numeric($row['duracion_seg'] ?? null) ? $row['duracion_seg'] : null,
                'VELOCIDAD_MAXIMA' => is_numeric($row['velocidad_maxima'] ?? null) ? $row['velocidad_maxima'] : null,
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

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function(BeforeImport $event) {
                ini_set('memory_limit', '2048M');
                set_time_limit(3600);
                DB::statement('SET SESSION wait_timeout = 28800');
                Log::info('Iniciando importación de Excesos', ['file' => $this->fileName]);
            },
            AfterImport::class => function(AfterImport $event) {
                Log::info('Importación de Excesos completada', ['file' => $this->fileName]);
                gc_collect_cycles();
            }
        ];
    }
}
