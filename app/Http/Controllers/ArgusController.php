<?php

namespace App\Http\Controllers;

use App\Exports\ArgusExport;
use App\Models\Argus;
use App\Models\Truck;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\JobStatusChecker;

class ArgusController extends Controller
{
    protected $jobStatusChecker;

    public function __construct(JobStatusChecker $jobStatusChecker)
    {
        $this->jobStatusChecker = $jobStatusChecker;
    }

    public function selectFiles()
    {
        // Verificar si hay jobs procesando archivos
        if ($this->jobStatusChecker->areJobsRunning()) {
            return view('argus.processing');
        }

        $truckFiles = Truck::select(
            DB::raw('MAX(fecha_salida) as fecha_registro'),
            DB::raw('MAX(updated_at) as updated_at'),
            DB::raw('MAX(created_at) as created_at')
        )->first();

        $argusFiles = Argus::select(
            'batch_id',
            'file_name',
            DB::raw('MAX(hora_alarma) as fecha_registro'),
            'final_status'
        )
            ->groupBy('batch_id', 'file_name', 'final_status')
            ->orderBy('final_status', 'desc')
            ->get();

        return view('argus.index', compact('truckFiles', 'argusFiles'));
    }

    public function processFiles(Request $request)
    {
        set_time_limit(1900);

        $request->validate([
            'argus_file' => [
                'required',
                'exists:arguses,batch_id',
                function ($attribute, $value, $fail) {
                    if (Argus::where('batch_id', $value)->doesntExist()) {
                        $fail('No hay registros en Argus para el batch seleccionado.');
                    }
                }
            ],
        ]);

        $batchId = $request->input('argus_file');

        // 1. Cargar trucks (dataset acotado, cabe en memoria)
        $truckData = Truck::select([
            'patente',
            'fecha_salida',
            'fecha_llegada',
            'hora_salida',
            'hora_llegada',
            'fecha_registro'
        ])->get();

        // 2. Validar fechas ANTES de procesar todo
        $maxFechaSalida = $truckData->max('fecha_salida');
        $maxDiaArgus    = Argus::where('batch_id', $batchId)->max('dia');

        if (Carbon::parse($maxFechaSalida)->lt(Carbon::parse($maxDiaArgus))) {
            return back()->with('error',
                "La fecha máxima de Truck ({$maxFechaSalida}) debe ser igual o mayor que la fecha máxima de Argus ({$maxDiaArgus})."
            );
        }

        // 3. Construir índice de trucks (solo arrays PHP, más liviano que Collections)
        $trucksIndexed = $this->buildTrucksIndex($truckData);
        unset($truckData); // liberar memoria inmediatamente

        Log::info("Índice de trucks construido para batch {$batchId}");

        // 4. Procesar Argus en chunks → solo guardar event_ids sin match
        $nonMatchEventIds = [];

        Argus::where('batch_id', $batchId)
            ->select(['patente', 'hora_alarma', 'event_id'])
            ->chunk(500, function ($chunk) use ($trucksIndexed, &$nonMatchEventIds) {
                foreach ($chunk as $argusRow) {
                    if (! $this->rowHasMatch($argusRow->patente, $argusRow->hora_alarma, $trucksIndexed)) {
                        $nonMatchEventIds[] = $argusRow->event_id;
                    }
                }
            });

        Log::info("Procesado batch {$batchId}: " . count($nonMatchEventIds) . " registros sin match.");

        // 5. Guardar SOLO los IDs en sesión (liviano)
        session([
            'argus_non_match_ids' => $nonMatchEventIds,
            'argus_batch_id'      => $batchId,
        ]);

        // 6. Procesar BD externa en chunks
        $this->processExternalDbChunked($trucksIndexed);

        // 7. Primera página para la vista (paginado, no se carga todo)
        $result = Argus::whereIn('event_id', $nonMatchEventIds)
            ->select([
                'patente', 'hora_alarma', 'dia', 'evento', 'motorista',
                'velocidade', 'latitude', 'longitude', 'operacion', 'event_id'
            ])
            ->paginate(100);

        return view('argus.compare', compact('result'));
    }

    // ─── Helpers privados ────────────────────────────────────────────────────────

    /**
     * Construye un índice patente → [{inicio, fin}] usando arrays PHP nativos.
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
     * Procesa la BD externa en chunks para no agotar memoria.
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

            Log::info('BD externa actualizada correctamente (chunk mode).');

        } catch (\Exception $e) {
            Log::error('processExternalDbChunked: ' . $e->getMessage());
        }
    }

    private function actualizarEstadosBDExterna($estados)
    {
        if (empty($estados)) {
            Log::info('No hay estados para actualizar');
            return;
        }

        try {
            Log::info('Actualizando ' . count($estados) . ' registros en BD externa');

            $this->verificarColumnaEstado();

            $lotes           = array_chunk($estados, 500);
            $totalActualizados = 0;

            foreach ($lotes as $lote) {
                $totalActualizados += $this->actualizarLote($lote);
            }

            Log::info("BD externa actualizada: {$totalActualizados} registros");

        } catch (\Exception $e) {
            Log::error('Error actualizando BD externa: ' . $e->getMessage());
        }
    }

    private function verificarColumnaEstado()
    {
        try {
            $testConnection = DB::connection('external_db')->select('SELECT 1 as test');
            Log::info('Conexión externa OK: ' . json_encode($testConnection));

            $tableExists = DB::connection('external_db')
                ->select("SELECT COUNT(*) as count FROM information_schema.TABLES
                          WHERE TABLE_SCHEMA = 'zsupagswcr'
                          AND TABLE_NAME = 'bajada_argus'");
            Log::info('Tabla bajada_argus existe: ' . $tableExists[0]->count);

            $existe = DB::connection('external_db')
                ->select("SELECT COUNT(*) as count FROM information_schema.COLUMNS
                          WHERE TABLE_SCHEMA = 'zsupagswcr'
                          AND TABLE_NAME = 'bajada_argus'
                          AND COLUMN_NAME = 'estado'");

            Log::info('Columna estado existe: ' . $existe[0]->count);

            if ($existe[0]->count == 0) {
                Log::info('Creando columna estado en bajada_argus');
                DB::connection('external_db')
                    ->statement('ALTER TABLE bajada_argus ADD COLUMN estado VARCHAR(50) NULL');
                Log::info('Columna estado creada exitosamente');
            }

            $totalRegistros = DB::connection('external_db')
                ->select('SELECT COUNT(*) as total FROM bajada_argus');
            Log::info('Total registros en bajada_argus: ' . $totalRegistros[0]->total);

        } catch (\Exception $e) {
            Log::error('Error verificando BD externa: ' . $e->getMessage());
            throw $e;
        }
    }

    private function actualizarLote($lote)
    {
        if (empty($lote)) {
            return 0;
        }

        try {
            Log::info('Actualizando lote de ' . count($lote) . ' registros');

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

            $parametros  = array_merge($valores, $ids);
            $actualizados = DB::connection('external_db')->update($sql, $parametros);

            Log::info('Registros actualizados en lote: ' . $actualizados);

            return $actualizados;

        } catch (\Exception $e) {
            Log::error('Error actualizando lote: ' . $e->getMessage());
            return 0;
        }
    }

    public function downloadExcel(Request $request)
    {
        $ids     = session('argus_non_match_ids');
        $batchId = session('argus_batch_id');

        if (empty($ids) || ! $batchId) {
            return back()->with('error', 'No hay datos disponibles para exportar.');
        }

        // Re-query limpio en lugar de deserializar la colección completa de sesión
        $result = Argus::whereIn('event_id', $ids)
            ->select([
                'patente', 'hora_alarma', 'dia', 'evento', 'motorista',
                'velocidade', 'latitude', 'longitude', 'operacion', 'event_id'
            ])
            ->get();

        return Excel::download(new ArgusExport($result), 'limpieza_argus.xlsx');
    }

    /**
     * Muestra la página de procesamiento de archivos.
     */
    public function show(Request $request, JobStatusChecker $jobStatusChecker)
    {
        $jobsRunning = $jobStatusChecker->areJobsRunning();

        if (! $jobsRunning && ! $request->session()->has('success')) {
            return redirect()->route('dashboard')->with('info', 'No hay archivos en procesamiento actualmente.');
        }

        return view('argus.processing');
    }

    public function processExternalFiles(Request $request)
    {
        set_time_limit(1900);

        $truckData = Truck::select([
            'patente',
            'fecha_salida',
            'fecha_llegada',
            'hora_salida',
            'hora_llegada',
            'fecha_registro'
        ])->get();

        $bajadaArgusData = DB::connection('external_db')->table('bajada_argus')
            ->select([
                'Frota as patente',
                'Hora_alarme as hora_alarma',
                'Hora_alarme as dia',
                'Evento as evento',
                DB::raw("'Sin datos' as motorista"),
                'Velocidade as velocidade',
                'Latitude as latitude',
                'Longitude as longitude',
                'Operacion as operacion',
                'id as event_id'
            ])
            ->get();

        $maxFechaSalida = $truckData->max('fecha_salida');
        $maxDiaArgus    = $bajadaArgusData->max('dia');

        if (Carbon::parse($maxFechaSalida)->lt(Carbon::parse($maxDiaArgus))) {
            return back()->with('error',
                "La fecha máxima de Truck ({$maxFechaSalida}) debe ser igual o mayor que la fecha máxima de bajada_argus ({$maxDiaArgus})."
            );
        }

        $trucksIndexed = $this->buildTrucksIndex($truckData);
        unset($truckData);

        $result = collect();

        foreach ($bajadaArgusData as $argusRow) {
            $match         = $this->rowHasMatch($argusRow->patente, $argusRow->hora_alarma, $trucksIndexed);
            $argusRow->estado = $match ? 'Viaje CBN' : 'NO VIAJE CBN';
            $result->push($argusRow);
        }

        unset($bajadaArgusData);

        try {
            $columns = DB::connection('external_db')->getSchemaBuilder()->getColumnListing('bajada_argus');

            if (! in_array('estado', $columns)) {
                DB::connection('external_db')->statement('ALTER TABLE bajada_argus ADD COLUMN estado VARCHAR(50) NULL');
                Log::info("Columna 'estado' creada en bajada_argus");
            }

            $lotes = $result->chunk(500);

            foreach ($lotes as $lote) {
                $loteArray = $lote->map(fn ($row) => [
                    'id'     => $row->event_id,
                    'estado' => $row->estado,
                ])->toArray();

                $this->actualizarLote($loteArray);
            }

            Log::info("Se actualizaron " . $result->count() . " registros en bajada_argus");

        } catch (\Exception $e) {
            Log::error("Error trabajando con la tabla bajada_argus: " . $e->getMessage());
        }

        $currentPage   = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $perPage       = 15;
        $paginatedResult = new \Illuminate\Pagination\LengthAwarePaginator(
            $result->forPage($currentPage, $perPage),
            $result->count(),
            $perPage,
            null,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        session(['excel_result' => $result]);

        return view('argus.compare_external', [
            'result'          => $paginatedResult,
            'truckFile'       => collect(), // ya liberado
            'bajadaArgusFile' => collect(), // ya liberado
        ]);
    }
}
