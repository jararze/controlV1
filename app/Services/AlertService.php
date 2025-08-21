<?php

namespace App\Services;
use App\Models\TruckTracking;
use Illuminate\Support\Facades\Log;

class AlertService
{
    private array $alertConfig;

    public function __construct()
    {
        $this->alertConfig = config('truck-tracking.alerts', [
            'critical_hours' => 48,
            'warning_hours' => 8,
            'normal_hours' => 4
        ]);
    }

    public function generateWaitingAlerts(): array
    {
        try {
            $minMinutes = $this->alertConfig['normal_hours'] * 60;

            $waitingTrucks = TruckTracking::inTransit()
                ->where('tiempo_espera_minutos', '>', $minMinutes)
                ->get();

            $alerts = [
                'critical' => [],
                'warning' => [],
                'attention' => [],
                'summary' => [
                    'total_waiting' => $waitingTrucks->count(),
                    'critical_count' => 0,
                    'warning_count' => 0,
                    'attention_count' => 0
                ]
            ];

            foreach ($waitingTrucks as $truck) {
                $horasEspera = $truck->tiempo_espera_horas;

                $alertData = [
                    'patente' => $truck->patente,
                    'planilla' => $truck->planilla,
                    'deposito_destino' => $truck->deposito_destino,
                    'horas_espera' => $horasEspera,
                    'estado_descarga' => $truck->estado_descarga,
                    'inicio_espera' => $truck->inicio_espera_descarga?->format('Y-m-d H:i:s'),
                    'status' => $truck->status,
                    'alert_level' => $truck->alert_level
                ];

                if ($horasEspera >= $this->alertConfig['critical_hours']) {
                    $alerts['critical'][] = $alertData;
                    $alerts['summary']['critical_count']++;
                } elseif ($horasEspera >= $this->alertConfig['warning_hours']) {
                    $alerts['warning'][] = $alertData;
                    $alerts['summary']['warning_count']++;
                } else {
                    $alerts['attention'][] = $alertData;
                    $alerts['summary']['attention_count']++;
                }
            }

            return $alerts;

        } catch (\Exception $e) {
            Log::error("Error generando alertas: {$e->getMessage()}");
            return [];
        }
    }

    public function logAlerts(array $alerts): void
    {
        if ($alerts['summary']['total_waiting'] > 0) {
            Log::info("📊 ALERTAS DE TIEMPO DE ESPERA:");
            Log::info("   🚨 Críticas (>{$this->alertConfig['critical_hours']}h): {$alerts['summary']['critical_count']}");
            Log::info("   ⚠️ Advertencias (>{$this->alertConfig['warning_hours']}h): {$alerts['summary']['warning_count']}");
            Log::info("   🔔 Atención (>{$this->alertConfig['normal_hours']}h): {$alerts['summary']['attention_count']}");

            // Log de casos críticos
            foreach ($alerts['critical'] as $alert) {
                Log::error("🚨 CRÍTICO: {$alert['patente']} - {$alert['horas_espera']}h en {$alert['deposito_destino']}");
            }
        }
    }
}
