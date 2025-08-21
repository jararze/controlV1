<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DeliveryCalculatorService
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

    public function calculateDeliveryProgress(array $geocercaStatus, ?string $depositoDestino = null): array
    {
        $porcentaje = 0.0;
        $estado = "EN_TRANSITO";

        // Lógica de porcentajes jerárquica
        if ($geocercaStatus['CIUDADES'] !== 'NO') {
            $porcentaje += 25.0;
            $estado = "EN_CIUDAD";
        }

        if ($geocercaStatus['CBN'] !== 'NO') {
            $porcentaje += 25.0;
            $estado = "EN_CENTRO_DISTRIBUCION";
        }

        if ($geocercaStatus['TRACK AND TRACE'] !== 'NO') {
            $porcentaje += 30.0; // Mayor peso
            $estado = "EN_ZONA_DESCARGA";
        }

        if ($geocercaStatus['DOCKS'] !== 'NO') {
            $porcentaje += 20.0; // Completar al 100%
            $estado = "DESCARGANDO";
        }

        // Si está en todos los puntos, alta probabilidad de descarga
        $allPresent = collect($geocercaStatus)->every(fn($status) => $status !== 'NO');
        if ($allPresent) {
            $porcentaje = 100.0;
            $estado = "DESCARGANDO_CONFIRMADO";
        }

        return [
            'porcentaje_entrega' => min($porcentaje, 100.0),
            'estado_entrega' => $estado
        ];
    }

    public function calculateWaitingTime(array $truckData, array $geocercaStatus, string $currentEstadoEntrega): array
    {
        try {
            $patente = $truckData['patente'];
            $planilla = $truckData['planilla'];

            // Verificar si el status es diferente de 'SALIDA' (criterio principal)
            $isWaitingForDischarge = ($truckData['status'] ?? '') !== 'SALIDA';

            // Si aún está en SALIDA, verificar geocercas como criterio secundario
            if (!$isWaitingForDischarge) {
                $isWaitingForDischarge = (
                    $geocercaStatus['DOCKS'] !== 'NO' ||
                    $geocercaStatus['TRACK AND TRACE'] !== 'NO' ||
                    in_array($currentEstadoEntrega, ['EN_ZONA_DESCARGA', 'DESCARGANDO', 'DESCARGANDO_CONFIRMADO'])
                );
            }

            // Determinar estado específico de descarga
            $estadoDescarga = $this->determineEstadoDescarga($truckData, $geocercaStatus, $currentEstadoEntrega);

            // Si no está esperando descarga, retornar valores por defecto
            if (!$isWaitingForDischarge) {
                return [
                    'tiempo_espera_minutos' => 0,
                    'inicio_espera_descarga' => null,
                    'estado_descarga' => 'NO_ESPERANDO',
                    'alert_level' => 'NORMAL'
                ];
            }

            // Buscar o crear inicio de espera
            $inicioEspera = $this->findOrCreateWaitingStart($patente, $planilla);

            if (!$inicioEspera) {
                $inicioEspera = Carbon::now();
            }

            // Calcular tiempo transcurrido
            $tiempoEspera = Carbon::now()->diffInMinutes($inicioEspera);
            $alertLevel = $this->calculateAlertLevel($tiempoEspera);

            return [
                'tiempo_espera_minutos' => $tiempoEspera,
                'inicio_espera_descarga' => $inicioEspera->format('Y-m-d H:i:s'),
                'estado_descarga' => $estadoDescarga,
                'alert_level' => $alertLevel
            ];

        } catch (\Exception $e) {
            Log::error("Error calculando tiempo de espera para {$truckData['patente']}: {$e->getMessage()}");

            return [
                'tiempo_espera_minutos' => 0,
                'inicio_espera_descarga' => null,
                'estado_descarga' => 'ERROR',
                'alert_level' => 'ERROR'
            ];
        }
    }

    private function determineEstadoDescarga(array $truckData, array $geocercaStatus, string $currentEstadoEntrega): string
    {
        if (($truckData['status'] ?? '') !== 'SALIDA') {
            return 'STATUS_' . ($truckData['status'] ?? 'UNKNOWN');
        }

        if ($geocercaStatus['DOCKS'] !== 'NO') {
            return 'EN_DOCKS';
        }

        if ($geocercaStatus['TRACK AND TRACE'] !== 'NO') {
            return 'EN_TRACK_TRACE';
        }

        if (in_array($currentEstadoEntrega, ['DESCARGANDO', 'DESCARGANDO_CONFIRMADO'])) {
            return 'DESCARGANDO';
        }

        if ($currentEstadoEntrega === 'EN_ZONA_DESCARGA') {
            return 'ZONA_DESCARGA';
        }

        return 'NO_ESPERANDO';
    }

    private function findOrCreateWaitingStart(string $patente, string $planilla): ?Carbon
    {
        // Buscar en el tracking actual
        $tracking = \App\Models\TruckTracking\TruckTracking::where('patente', $patente)
            ->where('planilla', $planilla)
            ->first();

        if ($tracking && $tracking->inicio_espera_descarga) {
            return Carbon::parse($tracking->inicio_espera_descarga);
        }

        // Buscar en historial el primer registro de espera
        $firstWaitingRecord = \App\Models\TruckTracking\TruckTrackingHistory::where('patente', $patente)
            ->where('planilla', $planilla)
            ->where(function($query) {
                $query->where('geocerca_docks', '!=', 'NO')
                    ->orWhere('geocerca_track_trace', '!=', 'NO')
                    ->orWhereIn('estado_entrega', ['EN_ZONA_DESCARGA', 'DESCARGANDO', 'DESCARGANDO_CONFIRMADO']);
            })
            ->orderBy('created_at', 'asc')
            ->first();

        if ($firstWaitingRecord) {
            return Carbon::parse($firstWaitingRecord->created_at);
        }

        // Si no hay registro previo, este es el primer momento de espera
        return Carbon::now();
    }

    private function calculateAlertLevel(int $tiempoEsperaMinutos): string
    {
        $tiempoEsperaHoras = $tiempoEsperaMinutos / 60;

        if ($tiempoEsperaHoras >= $this->alertConfig['critical_hours']) {
            return 'CRITICAL';
        }

        if ($tiempoEsperaHoras >= $this->alertConfig['warning_hours']) {
            return 'WARNING';
        }

        if ($tiempoEsperaHoras >= $this->alertConfig['normal_hours']) {
            return 'ATTENTION';
        }

        return 'NORMAL';
    }

    public function adjustTimeUtcMinus4($timeInput): ?string
    {
        try {
            if (!$timeInput) {
                return null;
            }

            if (is_string($timeInput)) {
                try {
                    $timeObj = Carbon::createFromFormat('H:i:s', $timeInput);
                } catch (\Exception $e) {
                    return $timeInput;
                }
            } elseif ($timeInput instanceof Carbon) {
                $timeObj = $timeInput;
            } else {
                return (string) $timeInput;
            }

            // Restar 1 hora (UTC-4)
            $adjusted = $timeObj->copy()->subHour();

            return $adjusted->format('H:i:s');

        } catch (\Exception $e) {
            Log::warning("Error ajustando hora UTC-4: {$e->getMessage()} - Input: {$timeInput}");
            return (string) $timeInput;
        }
    }
}
