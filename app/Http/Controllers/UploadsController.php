<?php

namespace App\Http\Controllers;

use App\Imports\ArgusPlanImport;
use App\Imports\MatrixPlanImport;
use App\Imports\TruckPlanImport;
use App\Jobs\ProcessArgusFile;
use App\Jobs\ProcessMatrixFile;
use App\Jobs\ProcessTruckFile;
use App\Models\Argus;
use App\Models\BatchCall;
use App\Models\Truck;
use App\Models\Uploads\Matrix;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class UploadsController extends Controller
{
    public function getMatriz()
    {
        return view('uploads.matriz');
    }

    public function postMatriz(Request $request)
    {


        // Validar el request
        $validator = Validator::make($request->all(), [
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:15240',
            'fecha_hora' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }


        try {
            $rutaArchivo = $request->file('archivo')->store('archivos', 'public');
            $fechaHora = $request->input('fecha_hora');
            $batchId = (string) Str::uuid();
            $fileName = $request->file('archivo')->getClientOriginalName();

            ProcessMatrixFile::dispatch($rutaArchivo, $batchId, $fechaHora, $fileName);

            // Redirigir con un mensaje de éxito
            return back()->with('success', 'Archivo subido e importado exitosamente con fecha y hora: '.$fechaHora);

        } catch (\Exception $e) {
            // Registrar el error en los logs
            Log::error('Error al importar el archivo', ['error' => $e->getMessage()]);

            // Redirigir con un mensaje de error
            return redirect()->back()->with('error', 'La hoja "BASE" no fue encontrada en el archivo Excel.');
        }
    }

    public function listMatrix()
    {
        $result = Matrix::select('batch_id', 'file_name', 'fecha_registro')
            ->distinct()
            ->get();

        return view('uploads.matriz.list', compact('result'));
    }

    public function destroy($batch_id)
    {
        // Find and delete all records with the specified batch_id
        $deletedRows = Matrix::where('batch_id', $batch_id)->delete();

        if ($deletedRows > 0) {
            return redirect()->back()->with('success', 'Files with the specified batch ID were deleted successfully.');
        } else {
            return redirect()->back()->with('error', 'No records found with the specified batch ID.');
        }
    }

    public function workWith(Request $request, $batch_id)
    {
        $origen = Matrix::where('batch_id', $batch_id)
            ->distinct()
            ->orderBy('dep_origen')
            ->pluck('dep_origen');

        $destino = Matrix::where('batch_id', $batch_id)
            ->distinct()
            ->orderBy('dep_destino')
            ->pluck('dep_destino');

        $tipoViaje = Matrix::where('batch_id', $batch_id)
            ->distinct()
            ->pluck('tipo_producto');

        $obsEta = Matrix::where('batch_id', $batch_id)
            ->distinct()
            ->pluck('obs_eta');

        $eta = Matrix::where('batch_id', $batch_id)
            ->distinct()
            ->pluck('eta');

        $productos = Matrix::where('batch_id', $batch_id)
            ->distinct()
            ->pluck('producto', 'cod_prod'); // Lista de productos con código y nombre

        $routeQuery = Matrix::query()
            ->where('eta', '!=', 'Planilla abierta')
            ->where('batch_id', $batch_id);

        if ($request->filled('tipo_producto')) {
            $routeQuery->where('tipo_producto', $request->tipo_producto);
        }
        if ($request->filled('origen')) {
            $routeQuery->where('dep_origen', $request->origen);
        }
        if ($request->filled('destino')) {
            $routeQuery->where('dep_destino', $request->destino);
        }
        if ($request->filled('patente')) {
            $routeQuery->where('patente', 'like', '%'.$request->patente.'%');
        }
        if ($request->has('eta') && is_array($request->eta) && count($request->eta) > 0) {
            $routeQuery->whereIn('eta', $request->eta);
        }
        if ($request->has('obs_eta') && is_array($request->obs_eta) && count($request->obs_eta) > 0) {
            $routeQuery->whereIn('obs_eta', $request->obs_eta);
        }
        if ($request->filled('conductor')) {
            $routeQuery->whereHas('driver', function ($query) use ($request) {
                $query->where('conductor', 'like', '%'.$request->conductor.'%');
            });
        }
        if ($request->has('cod_prod') && is_array($request->cod_prod) && count($request->cod_prod) > 0) {
            $routeQuery->whereIn('cod_prod', $request->cod_prod);
        }

        $routes = $routeQuery->select('dep_origen', 'dep_destino', 'patente', 'planilla', 'tipo_producto', 'eta',
            'obs_eta')
            ->distinct()
            ->orderBy('planilla', 'asc')
            ->get()
            ->map(function ($route) use ($batch_id) {
                if (is_numeric($route->eta)) {
                    $route->eta = Carbon::createFromFormat('Y-m-d', '1899-12-30')
                        ->addDays((int) $route->eta)
                        ->format('Y-m-d');
                }

                // Check if the planilla exists in batch_calls and assign a color
                $route->apply_styles = BatchCall::where('planilla', $route->planilla)
                    ->where('batch_id', $batch_id)
                    ->exists();
                return $route;
            });

        return view('uploads.matriz.workWith', [
            'results' => $routes,
            'batch_id' => $batch_id,
            'origenes' => $origen,
            'destinos' => $destino,
            'tipoViajes' => $tipoViaje,
            'obsEtas' => $obsEta,
            'etas' => $eta,
            'productos' => $productos,
        ]);
    }


    public function getTruck()
    {
        return view('uploads.truck');
    }

    public function postTruck(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt,text/plain,xlsx|max:15240',
            'fecha_hora' => 'required|date',
        ]);

        try {
            $file = $request->file('archivo');
            $fileName = $file->getClientOriginalName();

            // Almacenar en el directorio public (que tiene permisos adecuados)
            $filePath = $file->storeAs('uploads/temp', $fileName, 'public');

            $batchId = (string) Str::uuid();
            $fechaHora = $request->input('fecha_hora');

            // El path para job debe incluir 'public/'
            ProcessTruckFile::dispatch('public/' . $filePath, $batchId, $fechaHora, $fileName);

            return back()->with('success', 'Archivo subido correctamente. La importación se está procesando en segundo plano.');
        } catch (\Exception $e) {
            Log::error('Error al importar el archivo: '.$e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    public function listTruck()
    {
        $result = Truck::select(
            DB::raw('MAX(fecha_salida) as fecha_registro'),
            DB::raw('MAX(updated_at) as updated_at'),
            DB::raw('MAX(created_at) as created_at'))
            ->first();

        return view('uploads.truck.list', compact('result'));
    }


    public function getArgus()
    {
        return view('uploads.argus.index');
    }

    public function postArgus(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt,text/plain|max:10240',
            'fecha_hora' => 'required|date',
        ]);

        try {

            $rutaArchivo = $request->file('archivo')->store('archivos', 'public');
            $fechaHora = $request->input('fecha_hora');
            $batchId = (string) Str::uuid();
            $fileName = $request->file('archivo')->getClientOriginalName();

            // Despachar el job
            ProcessArgusFile::dispatch($rutaArchivo, $batchId, $fechaHora, $fileName);

            return back()->with('success', 'Archivo subido e importado exitosamente');

        } catch (\Exception $e) {
            // Registrar el error en los logs
            Log::error('Error al importar el archivo: '.$e->getMessage());

            // Redirigir con un mensaje de error
            return redirect()->back()->with('error', 'Error:' . $e->getMessage());
        }
    }


    public function listArgus()
    {
        $result = Argus::select('batch_id', 'file_name', DB::raw('MAX(hora_alarma) as fecha_registro'), 'final_status')
            ->groupBy('batch_id', 'file_name','final_status')
            ->orderBy('final_status', 'desc')
            ->get();

        return view('uploads.argus.list', compact('result'));
    }


}
