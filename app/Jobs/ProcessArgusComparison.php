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

            // P5: Pre-filtrar trucks solo a patentes que existen en el batch
            $patentesArgus = Argus::where('batch_id', $this->batchId)
                ->distinct()
                ->pluck('patente')
                ->toArray();

            $truckData = Truck::select([
                'patente', 'fecha_salida', 'fecha_llegada',
                'hora_salida', 'hora_llegada', 'fecha_registro',
            ])->whereIn('patente', $patentesArgus)->get();

            // P2: buildTrucksIndex usa strtotime() en vez de Carbon
            $trucksIndexed = $this->buildTrucksIndex($truckData);
            unset($truckData, $patentesArgus);

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

            // P1: Actualizar BD externa usando los IDs ya calculados, sin re-hacer matching
            $this->updateExternalDbWithIds($nonMatchEventIds);
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
     * P2: Construye índice patente → [{inicio, fin}] usando strtotime() en vez de Carbon.
     */
    private function buildTrucksIndex($truckData): array
    {
        $indexed = [];

        foreach ($truckData as $truck) {
            try {
                $fechaSalida = $truck->fecha_salida;
                $fechaLlegada = $truck->fecha_llegada;
                $fechaRegistro = $truck->fecha_registro;

                // Validar fechas sentinel (1999-11-30)
                if ($fechaSalida) {
                    $fsParsed = is_object($fechaSalida) ? $fechaSalida->format('Y-m-d') : substr($fechaSalida, 0, 10);
                    $fechaSalida = ($fsParsed === '1999-11-30') ? null : $fsParsed;
                }
                if ($fechaLlegada) {
                    $flParsed = is_object($fechaLlegada) ? $fechaLlegada->format('Y-m-d') : substr($fechaLlegada, 0, 10);
                    $fechaLlegada = ($flParsed === '1999-11-30') ? null : $flParsed;
                }

                $fechaSalida = $fechaSalida ?: (is_object($fechaRegistro) ? $fechaRegistro->format('Y-m-d') : substr($fechaRegistro ?? '', 0, 10));
                $fechaLlegada = $fechaLlegada ?: (is_object($fechaRegistro) ? $fechaRegistro->format('Y-m-d') : substr($fechaRegistro ?? '', 0, 10));

                $horaSalida = $truck->hora_salida ? trim($truck->hora_salida) : null;
                $horaLlegada = $truck->hora_llegada ? trim($truck->hora_llegada) : null;

                // Restar 1 hora a hora_salida
                if ($horaSalida) {
                    $ts = strtotime("1970-01-01 {$horaSalida}");
                    $ts -= 3600;
                    if ($ts < 0) {
                        // Cruzó medianoche hacia atrás: restar un día a la fecha
                        $ts += 86400;
                        $fechaSalida = date('Y-m-d', strtotime($fechaSalida) - 86400);
                    }
                    $horaSalida = date('H:i:s', $ts);
                }

                // Restar 1 hora a hora_llegada
                if ($horaLlegada) {
                    $ts = strtotime("1970-01-01 {$horaLlegada}");
                    $ts -= 3600;
                    if ($ts < 0) {
                        $ts += 86400;
                        $fechaLlegada = date('Y-m-d', strtotime($fechaLlegada) - 86400);
                    }
                    $horaLlegada = date('H:i:s', $ts);
                }

                $inicio = null;
                $fin = null;

                if ($fechaSalida && $horaSalida) {
                    $inicio = strtotime("{$fechaSalida} {$horaSalida}");
                } elseif ($fechaSalida) {
                    $inicio = strtotime($fechaSalida);
                }

                if ($fechaLlegada && $horaLlegada) {
                    $fin = strtotime("{$fechaLlegada} {$horaLlegada}");
                } elseif ($fechaLlegada) {
                    $fin = strtotime($fechaLlegada);
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
     * P2: Verifica match usando timestamps Unix en vez de Carbon.
     */
    private function rowHasMatch(string $patente, $horaAlarma, array $trucksIndexed): bool
    {
        if (! isset($trucksIndexed[$patente])) {
            return false;
        }

        $ts = is_object($horaAlarma) ? $horaAlarma->getTimestamp() : strtotime($horaAlarma);

        if ($ts === false) {
            return false;
        }

        foreach ($trucksIndexed[$patente] as $truck) {
            if ($truck['inicio'] && $truck['fin'] && $ts >= $truck['inicio'] && $ts <= $truck['fin']) {
                return true;
            }
        }

        return false;
    }

    /**
     * P1: Actualiza BD externa usando los non-match IDs ya calculados.
     * No re-ejecuta rowHasMatch().
     */
    private function updateExternalDbWithIds(array $nonMatchEventIds): void
    {
        try {
            $this->verificarColumnaEstado();

            // Marcar no-match en chunks de 500
            $chunks = array_chunk($nonMatchEventIds, 500);
            foreach ($chunks as $idChunk) {
                DB::connection('external_db')
                    ->table('bajada_argus')
                    ->whereIn('id', $idChunk)
                    ->update(['estado' => 'NO VIAJE CBN']);
            }

            // Marcar el resto como Viaje CBN (los que no están en non-match)
            // Hacerlo en batch: solo los que tienen estado NULL
            DB::connection('external_db')
                ->table('bajada_argus')
                ->whereNull('estado')
                ->update(['estado' => 'Viaje CBN']);

            Log::info('ProcessArgusComparison: BD externa actualizada.', [
                'non_match_actualizados' => count($nonMatchEventIds),
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessArgusComparison: error en BD externa — ' . $e->getMessage());
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
