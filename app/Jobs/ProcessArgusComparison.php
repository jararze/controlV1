<?php

namespace App\Jobs;

use App\Models\Argus;
use App\Models\Truck;
use App\Traits\LockFileProcessingTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessArgusComparison implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LockFileProcessingTrait;

    public $tries = 1;
    public $timeout = 1800;
    public $failOnTimeout = true;

    protected $batchId;
    protected $fileName;

    public function __construct(string $batchId)
    {
        $this->batchId = $batchId;
        $this->fileName = 'argus_comparison';
    }

    public function handle(): void
    {
        try {
            $totalArgus = Argus::where('batch_id', $this->batchId)->count();
            $this->createLockFile('argus_comparison', $totalArgus);

            Log::info("ProcessArgusComparison: iniciando batch {$this->batchId}", [
                'total_argus' => $totalArgus,
            ]);

            $truckData = Truck::select([
                'patente', 'fecha_salida', 'fecha_llegada',
                'hora_salida', 'hora_llegada', 'fecha_registro',
            ])->get();

            // Validar que los trucks cubran el rango temporal del batch de argus
            $maxFechaSalida = $truckData->max('fecha_salida');
            $maxDiaArgus    = Argus::where('batch_id', $this->batchId)->max('dia');
            if ($maxFechaSalida && $maxDiaArgus && Carbon::parse($maxFechaSalida)->lt(Carbon::parse($maxDiaArgus))) {
                Log::error("ProcessArgusComparison: fecha trucks ({$maxFechaSalida}) < fecha argus ({$maxDiaArgus})");
                throw new \Exception("Trucks desactualizados para este batch");
            }

            $trucksIndexed = $this->buildTrucksIndex($truckData);
            unset($truckData);

            Log::info("ProcessArgusComparison: índice de trucks construido", [
                'patentes' => count($trucksIndexed),
            ]);

            // 2. Procesar Argus en chunks — guardar solo event_ids sin match
            $nonMatchEventIds = [];
            $processed = 0;

            Cache::put("argus_progress_{$this->batchId}", [
                'processed' => 0,
                'total' => $totalArgus,
                'finished' => false,
            ], 14400);

            Argus::where('batch_id', $this->batchId)
                ->select(['patente', 'hora_alarma', 'event_id'])
                ->chunk(500, function ($chunk) use ($trucksIndexed, &$nonMatchEventIds, &$processed, $totalArgus) {
                    foreach ($chunk as $row) {
                        if (! $this->rowHasMatch($row->patente, $row->hora_alarma, $trucksIndexed)) {
                            $nonMatchEventIds[] = $row->event_id;
                        }
                    }

                    // P4: Cache::put por chunk, no cada 10 registros
                    $processed += $chunk->count();
                    Cache::put("argus_progress_{$this->batchId}", [
                        'processed' => $processed,
                        'total' => $totalArgus,
                        'finished' => false,
                    ], 14400);

                    $this->updateLockFileProgress('argus_comparison', $processed);
                });

            Log::info("ProcessArgusComparison: matching completado", [
                'total_procesados' => $processed,
                'sin_match' => count($nonMatchEventIds),
            ]);

            // Actualizar BD externa leyendo bajada_argus y matcheando cada registro contra el índice
            $this->processExternalDbChunked($trucksIndexed);
            unset($trucksIndexed);

            // 4. Guardar resultados en cache (TTL 4 horas)
            Cache::put("argus_progress_{$this->batchId}", [
                'processed' => $processed,
                'total' => $processed,
                'finished' => true,
            ], 14400);

            Cache::put("argus_comparison_{$this->batchId}", [
                'non_match_ids' => $nonMatchEventIds,
                'total_processed' => $processed,
                'total_non_match' => count($nonMatchEventIds),
                'completed_at' => now()->toDateTimeString(),
            ], 14400);

            $this->removeLockFile('argus_comparison');

            Log::info("ProcessArgusComparison: completado batch {$this->batchId}");

        } catch (\Exception $e) {
            $this->removeLockFile('argus_comparison');

            Log::error('ProcessArgusComparison: error', [
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->removeLockFile('argus_comparison');

        Log::error('ProcessArgusComparison: job falló', [
            'batch_id' => $this->batchId,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Construye índice patente → [{inicio, fin}] como objetos Carbon.
     */
    private function buildTrucksIndex($truckData): array
    {
        $indexed = [];

        foreach ($truckData as $truck) {
            try {
                $fechaSalida = ($truck->fecha_salida && Carbon::parse($truck->fecha_salida)->toDateString() !== '1999-11-30')
                    ? trim($truck->fecha_salida)
                    : $truck->fecha_registro;

                $fechaLlegada = ($truck->fecha_llegada && Carbon::parse($truck->fecha_llegada)->toDateString() !== '1999-11-30')
                    ? trim($truck->fecha_llegada)
                    : $truck->fecha_registro;

                $horaSalida  = $truck->hora_salida  ? trim($truck->hora_salida)  : null;
                $horaLlegada = $truck->hora_llegada ? trim($truck->hora_llegada) : null;

                if ($horaSalida) {
                    $c    = Carbon::createFromTimeString($horaSalida);
                    $orig = $c->hour;
                    $c->subHour();
                    if ($c->hour > $orig) {
                        $fechaSalida = Carbon::parse($fechaSalida)->subDay()->toDateString();
                    }
                    $horaSalida = $c->format('H:i:s');
                }

                if ($horaLlegada) {
                    $c    = Carbon::createFromTimeString($horaLlegada);
                    $orig = $c->hour;
                    $c->subHour();
                    if ($c->hour > $orig) {
                        $fechaLlegada = Carbon::parse($fechaLlegada)->subDay()->toDateString();
                    }
                    $horaLlegada = $c->format('H:i:s');
                }

                $inicio = null;
                $fin    = null;

                if ($fechaSalida && $horaSalida) {
                    $inicio = Carbon::parse($fechaSalida)->setTimeFromTimeString($horaSalida);
                } elseif ($fechaSalida) {
                    $inicio = Carbon::parse($fechaSalida);
                }

                if ($fechaLlegada && $horaLlegada) {
                    $fin = Carbon::parse($fechaLlegada)->setTimeFromTimeString($horaLlegada);
                } elseif ($fechaLlegada) {
                    $fin = Carbon::parse($fechaLlegada);
                }

                $indexed[$truck->patente][] = [
                    'inicio' => $inicio,
                    'fin'    => $fin,
                ];
            } catch (\Exception $e) {
                Log::error("buildTrucksIndex: patente {$truck->patente} — " . $e->getMessage());
            }
        }

        return $indexed;
    }

    /**
     * Verifica si una fila de Argus tiene match con algún viaje de truck.
     */
    private function rowHasMatch(string $patente, $horaAlarma, array $trucksIndexed): bool
    {
        if (! isset($trucksIndexed[$patente])) {
            return false;
        }

        try {
            $ts = Carbon::parse($horaAlarma);

            foreach ($trucksIndexed[$patente] as $truck) {
                if ($truck['inicio'] && $truck['fin'] && $ts->between($truck['inicio'], $truck['fin'])) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            Log::error("rowHasMatch: patente {$patente} — " . $e->getMessage());
        }

        return false;
    }

    /**
     * Procesa bajada_argus en chunks leyendo de la BD externa y matcheando
     * cada registro individualmente contra el índice de trucks.
     */
    private function processExternalDbChunked(array $trucksIndexed): void
    {
        try {
            $this->verificarColumnaEstado();

            DB::connection('external_db')
                ->table('bajada_argus')
                ->select(['id', 'Frota as patente', 'Hora_alarme as hora_alarma'])
                ->whereNull('estado')
                ->orderBy('id')
                ->chunk(500, function ($chunk) use ($trucksIndexed) {
                    $lote = [];

                    foreach ($chunk as $row) {
                        $match  = $this->rowHasMatch($row->patente, $row->hora_alarma, $trucksIndexed);
                        $lote[] = [
                            'id'     => $row->id,
                            'estado' => $match ? 'Viaje CBN' : 'NO VIAJE CBN',
                        ];
                    }

                    $this->actualizarLote($lote);
                });

            Log::info('ProcessArgusComparison: BD externa actualizada (chunk mode).');

        } catch (\Exception $e) {
            Log::error('processExternalDbChunked: ' . $e->getMessage());
        }
    }

    /**
     * Actualiza un lote de registros en bajada_argus usando UPDATE con CASE por id.
     */
    private function actualizarLote(array $lote): int
    {
        if (empty($lote)) {
            return 0;
        }

        try {
            $caseStatements = [];
            $valores        = [];
            $ids            = [];

            foreach ($lote as $item) {
                $caseStatements[] = "WHEN ? THEN ?";
                $valores[]        = $item['id'];
                $valores[]        = $item['estado'];
                $ids[]            = $item['id'];
            }

            $sql = "UPDATE bajada_argus
                    SET estado = CASE id " . implode(' ', $caseStatements) . " ELSE estado END
                    WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";

            return DB::connection('external_db')->update($sql, array_merge($valores, $ids));

        } catch (\Exception $e) {
            Log::error('actualizarLote: ' . $e->getMessage());
            return 0;
        }
    }

    private function verificarColumnaEstado(): void
    {
        $existe = DB::connection('external_db')
            ->select("SELECT COUNT(*) as count FROM information_schema.COLUMNS
                      WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'bajada_argus'
                      AND COLUMN_NAME = 'estado'");

        if ($existe[0]->count == 0) {
            DB::connection('external_db')
                ->statement('ALTER TABLE bajada_argus ADD COLUMN estado VARCHAR(50) NULL');
            Log::info('ProcessArgusComparison: columna estado creada en bajada_argus.');
        }
    }
}
