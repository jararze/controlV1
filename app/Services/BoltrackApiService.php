<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BoltrackApiService
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $this->baseUrl = config('truck-tracking.boltrack.base_url');
        $this->token = config('truck-tracking.boltrack.token');
    }

    public function getAllLocations(): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'token' => $this->token,
                    'Content-Type' => 'application/json'
                ])
                ->get($this->baseUrl . '/ultimaubicaciontodos');

            if ($response->successful()) {
                $data = $response->json();
                return $this->formatLocationsData($data);
            }

            Log::error("Error API Boltrack: Status {$response->status()}");
            return [];

        } catch (\Exception $e) {
            Log::error("Error consultando API Boltrack: {$e->getMessage()}");
            return [];
        }
    }

    public function getLocationForTruck(string $patente): ?array
    {
        $allLocations = $this->getAllLocations();
        return $allLocations[$patente] ?? null;
    }

    private function formatLocationsData(array $data): array
    {
        $locations = [];

        foreach ($data as $vehicle) {
            $patente = $vehicle['id_unidad'] ?? null;

            if ($patente) {
                $locations[$patente] = [
                    'patente' => $patente,
                    'latitude' => $vehicle['latitud'] ?? null,
                    'longitude' => $vehicle['longitud'] ?? null,
                    'timestamp' => $vehicle['tiempoMovimientoFormatted'] ?? null,
                    'speed' => $vehicle['velocidad_kmh'] ?? 0,
                    'direction' => $vehicle['direccion'] ?? 0,
                    'updated_at' => Carbon::now()
                ];
            }
        }

        Log::info("API Boltrack devolvió ubicaciones para " . count($locations) . " vehículos");
        return $locations;
    }
}
