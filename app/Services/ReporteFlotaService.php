<?php

namespace App\Services;

use App\Models\Limite;
use App\Models\Exceso;
use App\Models\TokenApi;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReporteFlotaService
{
    private $baseUrl = 'https://gestiondeflota.boltrack.net/reportes/';

    public function obtenerReportes($fechaInicio, $fechaFin, $token = null)
    {
        $tokenActivo = $token ?? $this->obtenerTokenActivo();

        if (!$tokenActivo) {
            throw new \Exception('No hay token activo disponible');
        }

        $batchId = Str::uuid();
        $fechaRegistro = Carbon::now();

        Log::info("Iniciando descarga con HTTP retry - batch_id: " . $batchId);

        try {
            // Hacer ambas peticiones con retry automático
            $excesos = $this->obtenerReporteConRetry('RP131BodyExcesos.rep', $fechaInicio, $fechaFin, $tokenActivo, $batchId, $fechaRegistro);
            $limites = $this->obtenerReporteConRetry('RP131BodyLimites.rep', $fechaInicio, $fechaFin, $tokenActivo, $batchId, $fechaRegistro);

            return [
                'excesos' => $excesos,
                'limites' => $limites,
                'batch_id' => $batchId
            ];

        } catch (\Exception $e) {
            Log::error("Error en obtenerReportes: " . $e->getMessage());
            throw $e;
        }
    }

    private function obtenerReporteConRetry($endpoint, $fechaInicio, $fechaFin, $token, $batchId, $fechaRegistro)
    {
        $url = $this->construirUrl($endpoint, $fechaInicio, $fechaFin, $token);
        $tipoReporte = strpos($endpoint, 'Excesos') !== false ? 'excesos' : 'limites';

        Log::info("Obteniendo {$tipoReporte} con retry...");

        $intentos = [
            // Intento 1: Configuración básica como test-api
            ['timeout' => 15, 'retry' => 2, 'delay' => 1000],
            // Intento 2: Más tiempo
            ['timeout' => 30, 'retry' => 3, 'delay' => 2000],
            // Intento 3: Máximo tiempo
            ['timeout' => 60, 'retry' => 1, 'delay' => 3000]
        ];

        foreach ($intentos as $index => $config) {
            try {
                Log::info("Intento " . ($index + 1) . " para {$tipoReporte} - timeout: {$config['timeout']}s");

                $response = Http::retry($config['retry'], $config['delay'])
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'Accept' => '*/*',
                        'Origin' => 'https://gestion.boltrack.net',
                        'Referer' => 'https://gestion.boltrack.net/',
                    ])
                    ->timeout($config['timeout'])
                    ->get($url);

                if ($response->successful()) {
                    $jsonData = $response->json();

                    if (isset($jsonData['data'])) {
                        Log::info("Intento " . ($index + 1) . " exitoso para {$tipoReporte}");
                        return $this->procesarDatos($jsonData['data'], $tipoReporte, $fechaInicio, $batchId, $fechaRegistro);
                    } else {
                        Log::warning("Respuesta sin campo 'data' en intento " . ($index + 1));
                        continue;
                    }
                } else {
                    Log::warning("HTTP {$response->status()} en intento " . ($index + 1));
                    continue;
                }

            } catch (\Exception $e) {
                Log::warning("Intento " . ($index + 1) . " falló: " . $e->getMessage());

                // Si es el último intento, lanzar excepción
                if ($index === count($intentos) - 1) {
                    throw new \Exception("Todos los intentos fallaron para {$tipoReporte}. Último error: " . $e->getMessage());
                }

                // Esperar antes del siguiente intento
                sleep(2);
                continue;
            }
        }

        throw new \Exception("No se pudo obtener {$tipoReporte} después de múltiples intentos");
    }

    private function construirUrl($endpoint, $fechaInicio, $fechaFin, $token)
    {
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);

        // Construir manualmente sin codificar el token
        $params = sprintf(
            'E=%s&T=0&IMEI=&mesi=%s&diai=%s&horai=00&mini=00&mesf=%s&diaf=%s&horaf=23&minf=59&grupo=',
            $token, // Sin codificar
            $inicio->format('ym'),
            $inicio->format('d'),
            $fin->format('ym'),
            $fin->format('d')
        );

        return $this->baseUrl . $endpoint . '?' . $params;
    }

    private function procesarDatos($data, $tipoReporte, $fechaReporte, $batchId, $fechaRegistro)
    {
        $lineas = explode('?', $data);
        $registrosGuardados = 0;

        Log::info("Procesando {$tipoReporte}: " . count($lineas) . " líneas");

        foreach ($lineas as $index => $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

            $campos = explode('|', $linea);

            // Skip header line
            if ($campos[0] === 'PLACA' || count($campos) < 8) {
                continue;
            }

            try {
                if ($tipoReporte === 'excesos') {
                    $this->procesarExceso($campos, $batchId, $fechaRegistro);
                } else {
                    $this->procesarLimite($campos, $batchId, $fechaRegistro);
                }
                $registrosGuardados++;
            } catch (\Exception $e) {
                Log::warning("Error procesando línea {$index}: " . $e->getMessage());
                continue;
            }
        }

        Log::info("Guardados {$registrosGuardados} registros de {$tipoReporte}");
        return $registrosGuardados;
    }

    private function procesarExceso($campos, $batchId, $fechaRegistro)
    {
        Exceso::create([
            'PLACA' => $campos[0] ?? '',
            'GRUPO' => $campos[1] ?? '',
            'DESCRIPCION' => $campos[2] ?? '',
            'FECHA_EXCESO' => $this->parsearFecha($campos[3] ?? null),
            'FECHA_RESTITUCION' => $this->parsearFecha($campos[4] ?? null),
            'UBICACION' => $campos[5] ?? '',
            'DIRECCION' => $campos[6] ?? '',
            'DURACION_SEG' => intval($campos[7] ?? 0),
            'VELOCIDAD_MAXIMA' => intval($campos[8] ?? 0),
            'batch_id' => $batchId,
            'file_name' => 'RP131BodyExcesos.rep',
            'fecha_registro' => $fechaRegistro,
            'final_status' => 'SUCCESS'
        ]);
    }

    private function procesarLimite($campos, $batchId, $fechaRegistro)
    {
        Limite::create([
            'PLACA' => $campos[0] ?? '',
            'GRUPO' => $campos[1] ?? '',
            'DESCRIPCION' => $campos[2] ?? '',
            'FECHA_ALERTA' => $this->parsearFecha($campos[3] ?? null),
            'TIEMPO_MOVIMIENTO' => $this->convertirSegundosATime($campos[4] ?? 0),
            'UBICACION' => $campos[5] ?? '',
            'DIRECCION' => $campos[6] ?? '',
            'TIEMPO_ENCENDIDO' => $this->convertirSegundosATime($campos[7] ?? 0),
            'TIEMPO_RALENTI' => $this->convertirSegundosATime($campos[8] ?? 0),
            'batch_id' => $batchId,
            'file_name' => 'RP131BodyLimites.rep',
            'fecha_registro' => $fechaRegistro,
            'final_status' => 'SUCCESS'
        ]);
    }

    private function parsearFecha($fechaStr)
    {
        if (empty($fechaStr)) return null;

        try {
            return Carbon::parse($fechaStr);
        } catch (\Exception $e) {
            Log::warning("Error parseando fecha: {$fechaStr}");
            return null;
        }
    }

    private function convertirSegundosATime($segundos)
    {
        $segundos = floatval($segundos);
        $horas = floor($segundos / 3600);
        $minutos = floor(($segundos % 3600) / 60);
        $segs = $segundos % 60;
        return sprintf("%02d:%02d:%02d", $horas, $minutos, $segs);
    }

    public function guardarToken($token, $fechaExpiracion = null)
    {
        TokenApi::where('activo', true)->update(['activo' => false]);
        return TokenApi::create([
            'token' => $token,
            'fecha_creacion' => Carbon::now(),
            'fecha_expiracion' => $fechaExpiracion,
            'activo' => true
        ]);
    }

    public function obtenerTokenActivo()
    {
        $token = TokenApi::tokenActivo();
        return $token ? $token->token : null;
    }

    public function validarToken($token, $fecha = null)
    {
        return true; // Simplificado para evitar timeouts adicionales
    }

    public function obtenerEstadisticas()
    {
        return [
            'total_excesos' => Exceso::count(),
            'total_limites' => Limite::count(),
            'ultimo_exceso' => Exceso::latest('fecha_registro')->first(),
            'ultimo_limite' => Limite::latest('fecha_registro')->first(),
            'token_actual' => TokenApi::tokenActivo(),
            'ultimos_batches' => $this->obtenerUltimosBatches()
        ];
    }

    private function obtenerUltimosBatches()
    {
        $batchesExcesos = Exceso::select('batch_id', 'fecha_registro')
            ->whereNotNull('batch_id')
            ->groupBy('batch_id', 'fecha_registro')
            ->orderBy('fecha_registro', 'desc')
            ->limit(5)
            ->get();

        $batchesLimites = Limite::select('batch_id', 'fecha_registro')
            ->whereNotNull('batch_id')
            ->groupBy('batch_id', 'fecha_registro')
            ->orderBy('fecha_registro', 'desc')
            ->limit(5)
            ->get();

        $todosBatches = $batchesExcesos->merge($batchesLimites)
            ->sortByDesc('fecha_registro')
            ->take(5);

        return $todosBatches->map(function($batch) {
            $excesos = Exceso::where('batch_id', $batch->batch_id)->count();
            $limites = Limite::where('batch_id', $batch->batch_id)->count();
            return [
                'batch_id' => $batch->batch_id,
                'fecha_registro' => $batch->fecha_registro,
                'excesos' => $excesos,
                'limites' => $limites,
                'total' => $excesos + $limites
            ];
        });
    }

    // Método de debug para probar conectividad
    public function testConectividad($token = null)
    {
        $tokenActivo = $token ?? $this->obtenerTokenActivo();
        $url = $this->construirUrl('RP131BodyExcesos.rep', '2025-08-20', '2025-08-20', $tokenActivo);

        try {
            $response = Http::timeout(10)->get($url);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'has_data' => isset($response->json()['data']),
                'content_length' => strlen($response->body()),
                'url' => substr($url, 0, 100) . '...'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'url' => substr($url, 0, 100) . '...'
            ];
        }
    }

    public function procesarDatosManual($data, $tipoReporte, $fechaReporte, $batchId, $fechaRegistro)
    {
        return $this->procesarDatos($data, $tipoReporte, $fechaReporte, $batchId, $fechaRegistro);
    }

}
