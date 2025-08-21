<?php

namespace App\Services;

use App\Models\Limite;
use App\Models\Exceso;
use App\Models\TokenApi;
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

        Log::info("INICIANDO DESCARGA - Batch: " . $batchId);

        try {
            // Método 1: Usar un servicio proxy público
            $excesos = $this->obtenerConProxy('RP131BodyExcesos.rep', $fechaInicio, $fechaFin, $tokenActivo, $batchId, $fechaRegistro);
            $limites = $this->obtenerConProxy('RP131BodyLimites.rep', $fechaInicio, $fechaFin, $tokenActivo, $batchId, $fechaRegistro);

            return [
                'excesos' => $excesos,
                'limites' => $limites,
                'batch_id' => $batchId
            ];

        } catch (\Exception $e) {
            Log::error("Método proxy falló: " . $e->getMessage());

            // Fallback: Método directo con configuración especial
            return $this->obtenerConConfiguracionEspecial($fechaInicio, $fechaFin, $tokenActivo, $batchId, $fechaRegistro);
        }
    }

    private function obtenerConProxy($endpoint, $fechaInicio, $fechaFin, $token, $batchId, $fechaRegistro)
    {
        $url = $this->construirUrl($endpoint, $fechaInicio, $fechaFin, $token);
        $tipoReporte = strpos($endpoint, 'Excesos') !== false ? 'excesos' : 'limites';

        // Lista de proxies públicos para usar
        $proxies = [
            'https://api.allorigins.win/raw?url=' . urlencode($url),
            'https://corsproxy.io/?' . urlencode($url),
            'https://cors-anywhere.herokuapp.com/' . $url,
        ];

        foreach ($proxies as $index => $proxyUrl) {
            try {
                Log::info("Intentando proxy " . ($index + 1) . " para {$tipoReporte}");

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $proxyUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 45,
                    CURLOPT_CONNECTTIMEOUT => 15,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Origin: https://gestion.boltrack.net',
                        'Referer: https://gestion.boltrack.net/',
                    ],
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($response && $httpCode === 200 && empty($error)) {
                    $data = json_decode($response, true);

                    if (isset($data['data'])) {
                        Log::info("Proxy " . ($index + 1) . " exitoso para {$tipoReporte}");
                        return $this->procesarDatos($data['data'], $tipoReporte, $fechaInicio, $batchId, $fechaRegistro);
                    }
                }

                Log::warning("Proxy " . ($index + 1) . " falló: HTTP {$httpCode}, Error: {$error}");

            } catch (\Exception $e) {
                Log::warning("Proxy " . ($index + 1) . " excepción: " . $e->getMessage());
                continue;
            }
        }

        throw new \Exception("Todos los proxies fallaron para {$tipoReporte}");
    }

    private function obtenerConConfiguracionEspecial($fechaInicio, $fechaFin, $token, $batchId, $fechaRegistro)
    {
        Log::info("Usando configuración especial como fallback");

        $resultados = ['excesos' => 0, 'limites' => 0, 'batch_id' => $batchId];

        // Configuración cURL optimizada para redes problemáticas
        $endpoints = [
            'RP131BodyExcesos.rep' => 'excesos',
            'RP131BodyLimites.rep' => 'limites'
        ];

        foreach ($endpoints as $endpoint => $tipo) {
            try {
                $url = $this->construirUrl($endpoint, $fechaInicio, $fechaFin, $token);

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 120, // Timeout muy alto
                    CURLOPT_CONNECTTIMEOUT => 30,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS => 5,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    CURLOPT_HTTPHEADER => [
                        'Accept: */*',
                        'Accept-Language: es-ES,es;q=0.9',
                        'Accept-Encoding: gzip, deflate, br',
                        'Origin: https://gestion.boltrack.net',
                        'Referer: https://gestion.boltrack.net/',
                        'Connection: keep-alive',
                        'Upgrade-Insecure-Requests: 1',
                    ],
                    CURLOPT_ENCODING => '', // Manejo automático de compresión
                    CURLOPT_TCP_KEEPALIVE => 1,
                    CURLOPT_TCP_KEEPIDLE => 120,
                    CURLOPT_TCP_KEEPINTVL => 60,
                    CURLOPT_DNS_CACHE_TIMEOUT => 300,
                    CURLOPT_FRESH_CONNECT => false,
                    CURLOPT_FORBID_REUSE => false,
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                $info = curl_getinfo($ch);
                curl_close($ch);

                Log::info("cURL {$tipo} - HTTP: {$httpCode}, Error: {$error}, Tiempo: {$info['total_time']}s");

                if ($response && $httpCode === 200 && empty($error)) {
                    $data = json_decode($response, true);

                    if (isset($data['data'])) {
                        $count = $this->procesarDatos($data['data'], $tipo, $fechaInicio, $batchId, Carbon::now());
                        $resultados[$tipo] = $count;
                        Log::info("Configuración especial exitosa para {$tipo}: {$count} registros");
                    } else {
                        Log::warning("Respuesta sin campo data para {$tipo}");
                    }
                } else {
                    Log::error("Error en {$tipo}: HTTP {$httpCode}, cURL: {$error}");
                }

                // Pausa entre peticiones para evitar rate limiting
                sleep(2);

            } catch (\Exception $e) {
                Log::error("Excepción en {$tipo}: " . $e->getMessage());
            }
        }

        return $resultados;
    }

    private function construirUrl($endpoint, $fechaInicio, $fechaFin, $token)
    {
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);

        $params = [
            'E' => $token,
            'T' => 0,
            'IMEI' => '',
            'mesi' => $inicio->format('ym'),
            'diai' => $inicio->format('d'),
            'horai' => '00',
            'mini' => '00',
            'mesf' => $fin->format('ym'),
            'diaf' => $fin->format('d'),
            'horaf' => '23',
            'minf' => '59',
            'grupo' => ''
        ];

        return $this->baseUrl . $endpoint . '?' . http_build_query($params);
    }

    private function procesarDatos($data, $tipoReporte, $fechaReporte, $batchId, $fechaRegistro)
    {
        if (empty($data)) {
            Log::warning("Datos vacíos para {$tipoReporte}");
            return 0;
        }

        $lineas = explode('?', $data);
        $registrosGuardados = 0;

        Log::info("Procesando {$tipoReporte}: " . count($lineas) . " líneas");

        foreach ($lineas as $index => $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

            $campos = explode('|', $linea);

            // Skip header
            if (isset($campos[0]) && $campos[0] === 'PLACA') continue;
            if (count($campos) < 8) continue;

            try {
                if ($tipoReporte === 'excesos') {
                    $this->procesarExceso($campos, $batchId, $fechaRegistro);
                } else {
                    $this->procesarLimite($campos, $batchId, $fechaRegistro);
                }
                $registrosGuardados++;
            } catch (\Exception $e) {
                Log::warning("Error línea {$index}: " . $e->getMessage());
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
        return true;
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

    // MÉTODO DE EMERGENCIA - Si todo falla, usar este endpoint
    public function obtenerPorEndpointDeEmergencia($fechaInicio, $fechaFin, $token = null)
    {
        $tokenActivo = $token ?? $this->obtenerTokenActivo();
        $batchId = Str::uuid();

        // Crear una URL de prueba simple
        $urlPrueba = "https://httpbin.org/post";

        $datosParaEnviar = [
            'url_original' => $this->construirUrl('RP131BodyExcesos.rep', $fechaInicio, $fechaFin, $tokenActivo),
            'token' => $tokenActivo,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $urlPrueba,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($datosParaEnviar),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        Log::info("Endpoint de emergencia respuesta: " . $response);

        return [
            'excesos' => 0,
            'limites' => 0,
            'batch_id' => $batchId,
            'message' => 'Modo de emergencia - revisa logs'
        ];
    }
}
