<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\Truck; // Tu modelo existente de camiones
use App\Models\TruckTracking;
use App\Models\TruckTrackingHistory;
use App\Services\BoltrackApiService;
use App\Services\GeocercaService;
use App\Services\DeliveryCalculatorService;
use App\Services\AlertService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateTrackingReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutos

    public function __construct() {}

    public function handle(
        BoltrackApiService $boltrackApi,
        GeocercaService $geocercaService,
        DeliveryCalculatorService $deliveryCalculator,
        AlertService $alertService
    ): void {
        $startTime = microtime(true);
        Log::info("🚀 Iniciando procesamiento de truck tracking...");

        try {
            // 1. Obtener camiones en tránsito desde tu modelo existente
            $trucks = $this->getTrucksInTransit();

            if ($trucks->isEmpty()) {
                Log::info("No hay camiones en tránsito");
                return;
            }

            Log::info("Encontrados {$trucks->count()} camiones en tránsito");

            // 2. Obtener todas las ubicaciones de la API
            $locations = $boltrackApi->getAllLocations();

            if (empty($locations)) {
                Log::error("No se pudieron obtener ubicaciones de la API");
                return;
            }

            // 3. Procesar cada camión
            $processed = 0;
            $errors = 0;

            foreach ($trucks as $truck) {
                try {
                    $this->processSingleTruck(
                        $truck,
                        $locations,
                        $geocercaService,
                        $deliveryCalculator
                    );
                    $processed++;
                } catch (\Exception $e) {
                    Log::error("Error procesando camión {$truck->patente}: {$e->getMessage()}");
                    $errors++;
                }
            }

            // 4. Generar alertas
            $alerts = $alertService->generateWaitingAlerts();
            $alertService->logAlerts($alerts);

            $elapsedTime = microtime(true) - $startTime;
            Log::info("🏁 Procesamiento completado en " . round($elapsedTime, 2) . "s: {$processed} exitosos, {$errors} errores");

        } catch (\Exception $e) {
            Log::error("Error en procesamiento principal: {$e->getMessage()}");
            throw $e;
        }
    }

    private function getTrucksInTransit()
    {
        // Usar tu modelo existente de camiones
        // Ajusta esta query según tu estructura de BD
        return Truck::where('status', 'SALIDA')
            ->whereRaw('NOT EXISTS (
                SELECT 1 FROM trucks t2
                WHERE t2.patente = trucks.patente
                AND (
                    t2.fecha_salida > trucks.fecha_salida
                    OR (t2.fecha_salida = trucks.fecha_salida AND t2.hora_salida > trucks.hora_salida)
                )
            )')
            ->orderBy('fecha_salida', 'desc')
            ->orderBy('hora_salida', 'desc')
            ->get();
    }

    private function processSingleTruck(
        $truck,
        array $locations,
        GeocercaService $geocercaService,
        DeliveryCalculatorService $deliveryCalculator
    ): void {
        $patente = $truck->patente;
        $location = $locations[$patente] ?? null;

        if (!$location || !$location['latitude'] || !$location['longitude']) {
            Log::warning("⚠️ {$patente}: Sin ubicación válida");
            return;
        }

        $lat = $location['latitude'];
        $lng = $location['longitude'];

        // Verificar geocercas
        $geocercaStatus = $geocercaService->checkPointInGeocercas(
            $lat,
            $lng,
            $truck->deposito_destino
        );

        // Calcular progreso de entrega
        $deliveryProgress = $deliveryCalculator->calculateDeliveryProgress(
            $geocercaStatus,
            $truck->deposito_destino
        );

        // Preparar datos del camión para cálculo de espera
        $truckData = [
            'patente' => $truck->patente,
            'planilla' => $truck->planilla,
            'status' => $truck->status
        ];

        // Calcular tiempo de espera
        $waitingData = $deliveryCalculator->calculateWaitingTime(
            $truckData,
            $geocercaStatus,
            $deliveryProgress['estado_entrega']
        );

        // Guardar o actualizar tracking
        $this->saveTruckTracking($truck, $location, $geocercaStatus, $deliveryProgress, $waitingData);

        // Log con información visual
        $this->logTruckProcessing($truck, $geocercaStatus, $deliveryProgress, $waitingData);
    }

    private function saveTruckTracking($truck, array $location, array $geocercaStatus, array $deliveryProgress, array $waitingData): void
    {
        // Buscar registro existente
        $tracking = TruckTracking::where('patente', $truck->patente)
            ->where('planilla', $truck->planilla)
            ->first();

        $trackingData = [
            'cod' => $truck->cod,
            'deposito_origen' => $truck->deposito_origen,
            'cod_destino' => $truck->cod_destino,
            'deposito_destino' => $truck->deposito_destino,
            'planilla' => $truck->planilla,
            'patente' => $truck->patente,
            'fecha_salida' => $truck->fecha_salida,
            'hora_salida' => $truck->hora_salida,
            'fecha_llegada' => $truck->fecha_llegada,
            'hora_llegada' => $truck->hora_llegada,
            'cod_producto' => $truck->cod_producto,
            'producto' => $truck->producto,
            'status' => $truck->status,
            'salida' => $truck->salida,
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'velocidad_kmh' => $location['speed'],
            'direccion' => $location['direction'],
            'api_timestamp' => $location['timestamp'],
            'geocerca_docks' => $geocercaStatus['DOCKS'],
            'geocerca_track_trace' => $geocercaStatus['TRACK AND TRACE'],
            'geocerca_cbn' => $geocercaStatus['CBN'],
            'geocerca_ciudades' => $geocercaStatus['CIUDADES'],
            'porcentaje_entrega' => $deliveryProgress['porcentaje_entrega'],
            'estado_entrega' => $deliveryProgress['estado_entrega'],
            'tiempo_espera_minutos' => $waitingData['tiempo_espera_minutos'],
            'estado_descarga' => $waitingData['estado_descarga']
        ];

        if ($tracking) {
            // Actualizar existente - mantener inicio_espera_descarga si ya existe
            if (!$tracking->inicio_espera_descarga && $waitingData['inicio_espera_descarga']) {
                $trackingData['inicio_espera_descarga'] = $waitingData['inicio_espera_descarga'];
            }

            $tracking->update($trackingData);
        } else {
            // Crear nuevo
            if ($waitingData['inicio_espera_descarga']) {
                $trackingData['inicio_espera_descarga'] = $waitingData['inicio_espera_descarga'];
            }

            $tracking = TruckTracking::create($trackingData);
        }

        // Guardar en historial
        TruckTrackingHistory::create([
            'patente' => $truck->patente,
            'planilla' => $truck->planilla,
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'velocidad_kmh' => $location['speed'],
            'direccion' => $location['direction'],
            'geocerca_docks' => $geocercaStatus['DOCKS'],
            'geocerca_track_trace' => $geocercaStatus['TRACK AND TRACE'],
            'geocerca_cbn' => $geocercaStatus['CBN'],
            'geocerca_ciudades' => $geocercaStatus['CIUDADES'],
            'porcentaje_entrega' => $deliveryProgress['porcentaje_entrega'],
            'estado_entrega' => $deliveryProgress['estado_entrega'],
            'tiempo_espera_minutos' => $waitingData['tiempo_espera_minutos'],
            'estado_descarga' => $waitingData['estado_descarga'],
            'api_timestamp' => $location['timestamp']
        ]);
    }

    private function logTruckProcessing($truck, array $geocercaStatus, array $deliveryProgress, array $waitingData): void
    {
        $patente = $truck->patente;
        $porcentaje = $deliveryProgress['porcentaje_entrega'];
        $estado = $deliveryProgress['estado_entrega'];

        // Información de tiempo de espera con emojis de alerta
        $tiempoEsperaStr = "";
        $alertEmoji = "";

        if ($waitingData['tiempo_espera_minutos'] > 0) {
            $horas = intval($waitingData['tiempo_espera_minutos'] / 60);
            $minutos = $waitingData['tiempo_espera_minutos'] % 60;

            switch ($waitingData['alert_level']) {
                case 'CRITICAL':
                    $alertEmoji = "🚨";
                    break;
                case 'WARNING':
                    $alertEmoji = "⚠️";
                    break;
                case 'ATTENTION':
                    $alertEmoji = "🔔";
                    break;
                default:
                    $alertEmoji = "⏰";
            }

            $tiempoEsperaStr = " {$alertEmoji} Esperando: {$horas}h {$minutos}m ({$waitingData['estado_descarga']})";
        }

        // Log de geocercas activas
        $inGeocerca = [];
        foreach ($geocercaStatus as $geo => $status) {
            if ($status !== 'NO') {
                $inGeocerca[] = "{$geo}: {$status}";
            }
        }

        if (!empty($inGeocerca)) {
            Log::info("✅ {$patente}: {$porcentaje}% ({$estado}) - " . implode(', ', $inGeocerca) . $tiempoEsperaStr);
        } else {
            Log::info("✅ {$patente}: {$porcentaje}% ({$estado}) - En tránsito libre{$tiempoEsperaStr}");
        }
    }
}
