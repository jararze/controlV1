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

class ArgusController extends Controller
{
    public function selectFiles()
    {
        $truckFiles = Truck::select(
            DB::raw('MAX(fecha_salida) as fecha_registro'),
            DB::raw('MAX(updated_at) as updated_at'),
            DB::raw('MAX(created_at) as created_at'))
            ->first();
        $argusFiles = Argus::select('batch_id', 'file_name', DB::raw('MAX(hora_alarma) as fecha_registro'),
            'final_status')
            ->groupBy('batch_id', 'file_name', 'final_status')
            ->orderBy('final_status', 'desc')
            ->get();

        return view('argus.index', compact('truckFiles', 'argusFiles'));
    }

    public function processFiles(Request $request)
    {
        ini_set('max_execution_time', 1900);
        ini_set('memory_limit', '10G');

        $request->validate([
            'argus_file' => [
                'required',
                'exists:arguses,batch_id',
                function ($attribute, $value, $fail) {
                    $count = Argus::where('batch_id', $value)->count();
                    if ($count === 0) {
                        $fail('No hay registros en Argus para el batch seleccionado.');
                    }
                }
            ],
        ]);

        // Obtener datos con selección específica de columnas
        $truckData = Truck::select([
            'patente',
            'fecha_salida',
            'fecha_llegada',
            'hora_salida',
            'hora_llegada',
            'fecha_registro'
        ])->get();

        $argusData = Argus::select([
            'patente',
            'hora_alarma',
            'dia',
            'evento',
            'motorista',
            'velocidade',
            'latitude',
            'longitude',
            'operacion',
            'batch_id',
            'event_id'
        ])
            ->where('batch_id', $request->input('argus_file'))
            ->get();

        // Validar fechas máximas
        $maxFechaSalida = $truckData->max('fecha_salida');
        $maxDiaArgus = $argusData->max('dia');

        if (Carbon::parse($maxFechaSalida)->lt(Carbon::parse($maxDiaArgus))) {
            return back()->with('error',
                'La fecha máxima de Truck ('.$maxFechaSalida.') debe ser igual o mayor que la fecha máxima de Argus ('.$maxDiaArgus.').');
        }

        // Pre-procesar y indexar los datos de truck
        $trucksIndexed = $truckData->groupBy('patente')->map(function ($trucks) {
            return $trucks->map(function ($truck) {
                try {
                    // Validación de fecha de salida como en el original
                    $fechaSalida = $truck->fecha_salida && Carbon::parse($truck->fecha_salida)->toDateString() !== '1999-11-30'
                        ? trim($truck->fecha_salida)
                        : $truck->fecha_registro;

                    $fechaLlegada = $truck->fecha_llegada && Carbon::parse($truck->fecha_llegada)->toDateString() !== '1999-11-30'
                        ? trim($truck->fecha_llegada)
                        : $truck->fecha_registro;

                    $horaSalida = $truck->hora_salida ? trim($truck->hora_salida) : null;
                    $horaLlegada = $truck->hora_llegada ? trim($truck->hora_llegada) : null;

                    // Restar una hora y ajustar fecha si es necesario
                    if ($horaSalida) {
                        $horaSalidaCarbon = Carbon::createFromTimeString($horaSalida);
                        $horaOriginal = $horaSalidaCarbon->hour;
                        $horaSalidaCarbon->subHour();

                        // Si al restar la hora pasamos a un día anterior
                        if ($horaSalidaCarbon->hour > $horaOriginal) {
                            $fechaSalida = Carbon::parse($fechaSalida)->subDay()->toDateString();
                        }
                        $horaSalida = $horaSalidaCarbon->format('H:i:s');
                    }

                    if ($horaLlegada) {
                        $horaLlegadaCarbon = Carbon::createFromTimeString($horaLlegada);
                        $horaOriginal = $horaLlegadaCarbon->hour;
                        $horaLlegadaCarbon->subHour();

                        // Si al restar la hora pasamos a un día anterior
                        if ($horaLlegadaCarbon->hour > $horaOriginal) {
                            $fechaLlegada = Carbon::parse($fechaLlegada)->subDay()->toDateString();
                        }
                        $horaLlegada = $horaLlegadaCarbon->format('H:i:s');
                    }

                    // Crear objetos Carbon para las comparaciones
                    $fechaHoraSalida = null;
                    $fechaHoraLlegada = null;

                    if ($fechaSalida && $horaSalida) {
                        $fechaHoraSalida = Carbon::parse($fechaSalida)->setTimeFromTimeString($horaSalida);
                    } elseif ($fechaSalida) {
                        $fechaHoraSalida = Carbon::parse($fechaSalida);
                    }

                    if ($fechaLlegada && $horaLlegada) {
                        $fechaHoraLlegada = Carbon::parse($fechaLlegada)->setTimeFromTimeString($horaLlegada);
                    } elseif ($fechaLlegada) {
                        $fechaHoraLlegada = Carbon::parse($fechaLlegada);
                    }

                    return [
                        'inicio' => $fechaHoraSalida,
                        'fin' => $fechaHoraLlegada,
                        'datos_originales' => [
                            'fecha_salida' => $fechaSalida,
                            'hora_salida' => $horaSalida,
                            'fecha_llegada' => $fechaLlegada,
                            'hora_llegada' => $horaLlegada
                        ]
                    ];

                } catch (\Exception $e) {
                    Log::error("Error procesando fechas para patente {$truck->patente}: " . $e->getMessage());
                    return null;
                }
            })->filter();
        });

        // Procesar registros de Argus
        $result = collect();
        foreach ($argusData as $argusRow) {
            $matchFound = false;

            if (isset($trucksIndexed[$argusRow->patente])) {
                try {
                    $horaAlarma = Carbon::parse($argusRow->hora_alarma);

                    foreach ($trucksIndexed[$argusRow->patente] as $truck) {
                        if ($truck['inicio'] &&
                            $truck['fin'] &&
                            $horaAlarma->between($truck['inicio'], $truck['fin'])) {
                            $matchFound = true;
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error procesando hora de alarma para Argus ID {$argusRow->id}: " . $e->getMessage());
                }
            }

            if (!$matchFound) {
                $result->push($argusRow);
            }
        }

        session(['excel_result' => $result]);
        return view('argus.compare', [
            'result' => $result,
            'truckFile' => $truckData,
            'argusFile' => $argusData
        ]);
    }

    public function downloadExcel(Request $request)
    {
        $result = session('excel_result');

        if (!$result) {
            return back()->with('error', 'No hay datos disponibles para exportar.');
        }
        return Excel::download(new ArgusExport($result), 'limpieza_argus.xlsx');
    }

}
