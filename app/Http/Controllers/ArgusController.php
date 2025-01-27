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
        $truckFiles = Truck::select('batch_id', 'file_name', DB::raw('MAX(fecha_salida) as fecha_registro'),
            'final_status')
            ->groupBy('batch_id', 'file_name', 'final_status')
            ->orderBy('final_status', 'desc')
            ->get();
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
            'truck_file' => 'required|exists:trucks,batch_id',
            'argus_file' => 'required|exists:arguses,batch_id',
        ]);

        $truckFile = Truck::where('batch_id', $request->input('truck_file'))->get();
        $argusFile = Argus::where('batch_id', $request->input('argus_file'))->get();

        $maxFechaSalida = $truckFile->max('fecha_salida');
        $maxDiaArgus = $argusFile->max('dia');

        // Validar que la fecha máxima de Truck sea igual o mayor que la de Argus
        if (Carbon::parse($maxFechaSalida)->lt(Carbon::parse($maxDiaArgus))) {
            return back()->with('error',
                'La fecha máxima de Truck ('.$maxFechaSalida.') debe ser igual o mayor que la fecha máxima de Argus ('.$maxDiaArgus.').');
        }

        $result = [];
        foreach ($argusFile as $argusRow) {

            $matchingTrucks = $truckFile->where('patente', $argusRow->patente);

            if ($matchingTrucks->isNotEmpty()) {

                $validMatch = false;

                foreach ($matchingTrucks as $matchingTruck) {
                    $fechaSalida = $matchingTruck->fecha_salida && Carbon::parse($matchingTruck->fecha_salida)->toDateString() !== '1999-11-30'
                        ? trim($matchingTruck->fecha_salida)
                        : $matchingTruck->fecha_registro;

                    $fechaLlegada = $matchingTruck->fecha_llegada && Carbon::parse($matchingTruck->fecha_llegada)->toDateString() !== '1999-11-30'
                        ? trim($matchingTruck->fecha_llegada)
                        : $matchingTruck->fecha_registro;

                    $horaSalida = $matchingTruck->hora_salida ? trim($matchingTruck->hora_salida) : null;
                    $horaLlegada = $matchingTruck->hora_llegada ? trim($matchingTruck->hora_llegada) : null;

                    if ($horaSalida) {
                        $horaSalidaCarbon = Carbon::createFromTimeString($horaSalida)->subHour();
                        if ($horaSalidaCarbon->hour > Carbon::createFromTimeString($horaSalida)->hour) {
                            $fechaSalida = Carbon::parse($fechaSalida)->subDay()->toDateString();
                        }
                        $horaSalida = $horaSalidaCarbon->toTimeString();
                    }

                    if ($horaLlegada) {
                        $horaLlegadaCarbon = Carbon::createFromTimeString($horaLlegada)->subHour();
                        if ($horaLlegadaCarbon->hour > Carbon::createFromTimeString($horaLlegada)->hour) {
                            $fechaLlegada = Carbon::parse($fechaLlegada)->subDay()->toDateString();
                        }
                        $horaLlegada = $horaLlegadaCarbon->toTimeString();
                    }

                    $fechaHoraSalida = null;
                    $fechaHoraLlegada = null;

                    try {
                        if ($fechaSalida && $horaSalida) {
                            $fechaHoraSalida = Carbon::parse($fechaSalida)->setTimeFromTimeString($horaSalida);
                        } elseif ($fechaSalida) {
                            $fechaHoraSalida = Carbon::parse($fechaSalida);
                        }
                    } catch (\Exception $e) {
                        Log::error("Error al procesar fechaHoraSalida: ".$e->getMessage());
                    }

                    try {
                        if ($fechaLlegada && $horaLlegada) {
                            $fechaHoraLlegada = Carbon::parse($fechaLlegada)->setTimeFromTimeString($horaLlegada);
                        } elseif ($fechaLlegada) {
                            $fechaHoraLlegada = Carbon::parse($fechaLlegada);
                        }
                    } catch (\Exception $e) {
                        Log::error("Error al procesar fechaHoraLlegada: ".$e->getMessage());
                    }

                    if ($fechaHoraSalida && $fechaHoraLlegada) {
                        try {
                            $horaAlarma = Carbon::parse($argusRow->hora_alarma);
                            if ($horaAlarma->between($fechaHoraSalida, $fechaHoraLlegada)) {
                                $validMatch = true;
                                break; // No es necesario seguir buscando en más coincidencias
                            }
                        } catch (\Exception $e) {
                            Log::error("Error al procesar hora de alarma: ".$e->getMessage());
                        }
                    }
                }

                if ($validMatch) {
                    continue;
                }
            }

            $result[] = $argusRow;
        }
        session(['excel_result' => $result]);
        return view('argus.compare', compact('result', 'truckFile', 'argusFile'));
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
