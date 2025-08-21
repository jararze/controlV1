<?php

namespace App\Services;

use App\Models\TruckTracking;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ExcelReportService
{
    public function __construct()
    {
    }

    public function generateTrackingReport(array $filters = []): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "tracking_report_{$timestamp}.xlsx";
        $filePath = "truck-tracking/reports/{$filename}";

        // Construir query con filtros
        $query = TruckTracking::query();

        if (!empty($filters['patente'])) {
            $query->where('patente', 'like', '%' . $filters['patente'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['deposito_destino'])) {
            $query->where('deposito_destino', $filters['deposito_destino']);
        }

        if (!empty($filters['estado_entrega'])) {
            $query->where('estado_entrega', $filters['estado_entrega']);
        }

        $data = $query->orderBy('updated_at', 'desc')->get();

        // Preparar datos para Excel
        $excelData = $data->map(function ($truck) {
            return [
                'patente' => $truck->patente,
                'planilla' => $truck->planilla,
                'status' => $truck->status,
                'deposito_origen' => $truck->deposito_origen,
                'deposito_destino' => $truck->deposito_destino,
                'producto' => $truck->producto,
                'cod_producto' => $truck->cod_producto,
                'salida' => $truck->salida,
                'fecha_salida' => $truck->fecha_salida?->format('Y-m-d'),
                'hora_salida' => $truck->hora_salida,
                'fecha_llegada' => $truck->fecha_llegada?->format('Y-m-d'),
                'hora_llegada' => $truck->hora_llegada,
                'latitude' => $truck->latitude,
                'longitude' => $truck->longitude,
                'velocidad_kmh' => $truck->velocidad_kmh,
                'timestamp' => $truck->api_timestamp,
                'en_docks' => $truck->geocerca_docks,
                'en_track_trace' => $truck->geocerca_track_trace,
                'en_cbn' => $truck->geocerca_cbn,
                'en_ciudades' => $truck->geocerca_ciudades,
                'porcentaje_entrega' => $truck->porcentaje_entrega,
                'estado_entrega' => $truck->estado_entrega,
                'tiempo_espera_minutos' => $truck->tiempo_espera_minutos,
                'tiempo_espera_horas' => $truck->tiempo_espera_horas,
                'estado_descarga' => $truck->estado_descarga,
                'alert_level' => $truck->alert_level,
                'inicio_espera' => $truck->inicio_espera_descarga?->format('Y-m-d H:i:s'),
                'fecha_proceso' => $truck->updated_at->format('Y-m-d H:i:s')
            ];
        })->toArray();

        // Crear archivo Excel
        Excel::store(new TrackingReportExport($excelData), $filePath);

        return Storage::path($filePath);
    }
}
