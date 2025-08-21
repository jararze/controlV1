<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TruckTracking;
use App\Models\TruckTrackingHistory;
use App\Services\AlertService;
use App\Services\ExcelReportService;
use App\Jobs\ProcessTruckTracking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TruckTrackingController extends Controller
{
    public function __construct(
        private AlertService $alertService,
        private ExcelReportService $excelReportService
    ) {}

    public function index(Request $request)
    {
        $query = TruckTracking::query();

        // Filtros
        if ($request->filled('patente')) {
            $query->where('patente', 'like', '%' . $request->patente . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('deposito_destino')) {
            $query->where('deposito_destino', $request->deposito_destino);
        }

        if ($request->filled('estado_entrega')) {
            $query->where('estado_entrega', $request->estado_entrega);
        }

        if ($request->filled('alert_level')) {
            $alertLevel = $request->alert_level;
            $query->whereRaw("
                CASE
                    WHEN tiempo_espera_minutos >= ? THEN 'CRITICAL'
                    WHEN tiempo_espera_minutos >= ? THEN 'WARNING'
                    WHEN tiempo_espera_minutos >= ? THEN 'ATTENTION'
                    ELSE 'NORMAL'
                END = ?
            ", [
                config('truck-tracking.alerts.critical_hours', 48) * 60,
                config('truck-tracking.alerts.warning_hours', 8) * 60,
                config('truck-tracking.alerts.normal_hours', 4) * 60,
                $alertLevel
            ]);
        }

        $trucks = $query->latest('updated_at')->paginate(50);

        // Estadísticas generales
        $stats = [
            'total' => TruckTracking::count(),
            'in_transit' => TruckTracking::inTransit()->count(),
            'waiting_discharge' => TruckTracking::waitingForDischarge()->count(),
            'critical_waiting' => TruckTracking::criticalWaiting()->count(),
        ];

        return view('truck-tracking.index', compact('trucks', 'stats'));
    }

    public function show(TruckTracking $truckTracking)
    {
        $truckTracking->load('history');

        // Últimas 24 horas de historial
        $recentHistory = $truckTracking->history()
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('truck-tracking.show', compact('truckTracking', 'recentHistory'));
    }

    public function dashboard()
    {
        // Estadísticas del dashboard
        $stats = [
            'total_trucks' => TruckTracking::count(),
            'in_transit' => TruckTracking::inTransit()->count(),
            'in_docks' => TruckTracking::inGeocerca('docks')->count(),
            'in_track_trace' => TruckTracking::inGeocerca('track and trace')->count(),
            'waiting_discharge' => TruckTracking::waitingForDischarge()->count(),
        ];

        // Alertas recientes
        $alerts = $this->alertService->generateWaitingAlerts();

        // Distribución por estado de entrega
        $estadosEntrega = TruckTracking::selectRaw('estado_entrega, COUNT(*) as count')
            ->groupBy('estado_entrega')
            ->pluck('count', 'estado_entrega')
            ->toArray();

        return view('truck-tracking.dashboard', compact('stats', 'alerts', 'estadosEntrega'));
    }

    public function processTracking(): JsonResponse
    {
        try {
            ProcessTruckTracking::dispatch();

            return response()->json([
                'success' => true,
                'message' => 'Procesamiento de tracking iniciado en segundo plano'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error iniciando procesamiento: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateReport(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['patente', 'status', 'deposito_destino', 'estado_entrega']);

            $filename = $this->excelReportService->generateTrackingReport($filters);

            return response()->json([
                'success' => true,
                'message' => 'Reporte generado exitosamente',
                'filename' => $filename,
                'download_url' => route('truck-tracking.download-report', ['filename' => basename($filename)])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generando reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadReport(string $filename)
    {
        $path = storage_path('app/truck-tracking/reports/' . $filename);

        if (!file_exists($path)) {
            abort(404, 'Archivo no encontrado');
        }

        return response()->download($path)->deleteFileAfterSend();
    }

    public function alerts(): JsonResponse
    {
        $alerts = $this->alertService->generateWaitingAlerts();
        return response()->json($alerts);
    }

    public function updateTruckStatus(Request $request, TruckTracking $truckTracking): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|max:50',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $truckTracking->update([
                'status' => $request->status,
                'updated_at' => Carbon::now()
            ]);

            // Log en historial si es necesario
            if ($request->filled('notes')) {
                TruckTrackingHistory::create([
                    'patente' => $truckTracking->patente,
                    'planilla' => $truckTracking->planilla,
                    'latitude' => $truckTracking->latitude,
                    'longitude' => $truckTracking->longitude,
                    'velocidad_kmh' => $truckTracking->velocidad_kmh,
                    'direccion' => $truckTracking->direccion,
                    'geocerca_docks' => $truckTracking->geocerca_docks,
                    'geocerca_track_trace' => $truckTracking->geocerca_track_trace,
                    'geocerca_cbn' => $truckTracking->geocerca_cbn,
                    'geocerca_ciudades' => $truckTracking->geocerca_ciudades,
                    'porcentaje_entrega' => $truckTracking->porcentaje_entrega,
                    'estado_entrega' => $truckTracking->estado_entrega,
                    'tiempo_espera_minutos' => $truckTracking->tiempo_espera_minutos,
                    'estado_descarga' => $truckTracking->estado_descarga,
                    'api_timestamp' => 'Manual update: ' . $request->notes,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Status actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error actualizando status: ' . $e->getMessage()
            ], 500);
        }
    }
}
