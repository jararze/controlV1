<?php

namespace App\Services;

use App\Models\ConduccionFueraHorario;
use App\Models\Limite;
use App\Models\Exceso;
use App\Models\TokenApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReporteFlotaService
{
    private $baseUrl = 'https://gestiondeflota.boltrack.net/reportes/';

    public function obtenerReportes($fechaInicio, $fechaFin, $token = null)
    {
        // NUEVA VALIDACIÓN DE DUPLICADOS
        $verificacion = $this->verificarDuplicados($fechaInicio, $fechaFin);
        if ($verificacion['tiene_duplicados']) {
            return [
                'excesos' => 0,
                'limites' => 0,
                'batch_id' => null,
                'duplicados_detectados' => true,
                'mensaje_duplicados' => $verificacion['mensaje'],
                'detalles_duplicados' => $verificacion['detalles']
            ];
        }

        $tokenActivo = $token ?? $this->obtenerTokenActivo();

        if (!$tokenActivo) {
            throw new \Exception('No hay token activo disponible');
        }

        $batchId = Str::uuid();
        $fechaRegistro = Carbon::now();

        Log::info("Iniciando descarga con HTTP retry - batch_id: " . $batchId);

        // LOG DE LA URL GENERADA - NUEVO
        $urlExcesos = $this->construirUrl('RP131BodyExcesos.rep', $fechaInicio, $fechaFin, $tokenActivo);
        $urlLimites = $this->construirUrl('RP131BodyLimites.rep', $fechaInicio, $fechaFin, $tokenActivo);

        Log::info("URL EXCESOS: " . $urlExcesos);
        Log::info("URL LIMITES: " . $urlLimites);

        try {
            // Hacer ambas peticiones con retry automático
            $excesos = $this->obtenerReporteConRetry('RP131BodyExcesos.rep', $fechaInicio, $fechaFin, $tokenActivo, $batchId, $fechaRegistro);
            $limites = $this->obtenerReporteConRetry('RP131BodyLimites.rep', $fechaInicio, $fechaFin, $tokenActivo, $batchId, $fechaRegistro);
            $conduccionFueraHorario = $this->obtenerReporteConRetry('RP022BodyV05.rep', $fechaInicio, $fechaFin, $tokenActivo, $batchId, $fechaRegistro, ['vPrimSec' => '0']);

            return [
                'excesos' => $excesos,
                'limites' => $limites,
                'conduccion_fuera_horario' => $conduccionFueraHorario,
                'batch_id' => $batchId,
                'duplicados_detectados' => false,
                'debug_urls' => [
                    'excesos' => $this->construirUrl('RP131BodyExcesos.rep', $fechaInicio, $fechaFin, $tokenActivo),
                    'limites' => $this->construirUrl('RP131BodyLimites.rep', $fechaInicio, $fechaFin, $tokenActivo),
                    'conduccion_fuera_horario' => $this->construirUrl('RP022BodyV05.rep', $fechaInicio, $fechaFin, $tokenActivo, ['vPrimSec' => '0']) // NUEVA LÍNEA
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Error en obtenerReportes: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verifica si ya existen registros para el rango de fechas
     */
    public function verificarDuplicados($fechaInicio, $fechaFin)
    {
        $fechaInicioCarbon = Carbon::parse($fechaInicio)->startOfDay();
        $fechaFinCarbon = Carbon::parse($fechaFin)->endOfDay();

        // Buscar excesos en el rango de fechas
        $excesosExistentes = Exceso::whereBetween('FECHA_EXCESO', [$fechaInicioCarbon, $fechaFinCarbon])
            ->count();

        // Buscar límites en el rango de fechas
        $limitesExistentes = Limite::whereBetween('FECHA_ALERTA', [$fechaInicioCarbon, $fechaFinCarbon])
            ->count();

        $totalExistentes = $excesosExistentes + $limitesExistentes;

        if ($totalExistentes > 0) {
            // Obtener detalles de los batches existentes
            $batchesExcesos = Exceso::whereBetween('FECHA_EXCESO', [$fechaInicioCarbon, $fechaFinCarbon])
                ->select('batch_id', 'fecha_registro')
                ->distinct()
                ->get();

            $batchesLimites = Limite::whereBetween('FECHA_ALERTA', [$fechaInicioCarbon, $fechaFinCarbon])
                ->select('batch_id', 'fecha_registro')
                ->distinct()
                ->get();

            $todosBatches = $batchesExcesos->merge($batchesLimites)
                ->unique('batch_id')
                ->sortByDesc('fecha_registro');

            $detallesBatches = $todosBatches->map(function($batch) {
                return [
                    'batch_id' => substr($batch->batch_id, 0, 8) . '...',
                    'fecha_registro' => $batch->fecha_registro->format('d/m/Y H:i:s')
                ];
            })->toArray();

            return [
                'tiene_duplicados' => true,
                'mensaje' => "Ya existen {$totalExistentes} registros para el rango de fechas {$fechaInicioCarbon->format('d/m/Y')} - {$fechaFinCarbon->format('d/m/Y')}. ({$excesosExistentes} excesos y {$limitesExistentes} límites)",
                'detalles' => [
                    'total_registros' => $totalExistentes,
                    'excesos' => $excesosExistentes,
                    'limites' => $limitesExistentes,
                    'batches_existentes' => $detallesBatches,
                    'fecha_inicio' => $fechaInicioCarbon->format('d/m/Y'),
                    'fecha_fin' => $fechaFinCarbon->format('d/m/Y')
                ]
            ];
        }

        return [
            'tiene_duplicados' => false,
            'mensaje' => 'No se encontraron registros duplicados',
            'detalles' => []
        ];
    }

    /**
     * Método para forzar descarga (ignorando duplicados)
     */
    public function obtenerReportesForzado($fechaInicio, $fechaFin, $token = null)
    {
        $tokenActivo = $token ?? $this->obtenerTokenActivo();

        if (!$tokenActivo) {
            throw new \Exception('No hay token activo disponible');
        }

        $batchId = Str::uuid();
        $fechaRegistro = Carbon::now();

        Log::info("Iniciando descarga FORZADA (ignorando duplicados) - batch_id: " . $batchId);

        try {
            $excesos = $this->obtenerReporteConRetry('RP131BodyExcesos.rep', $fechaInicio, $fechaFin, $tokenActivo, $batchId, $fechaRegistro);
            $limites = $this->obtenerReporteConRetry('RP131BodyLimites.rep', $fechaInicio, $fechaFin, $tokenActivo, $batchId, $fechaRegistro);
            $conduccionFueraHorario = $this->obtenerReporteConRetry('RP022BodyV05.rep', $fechaInicio, $fechaFin, $tokenActivo, $batchId, $fechaRegistro, ['vPrimSec' => '0']);

            return [
                'excesos' => $excesos,
                'limites' => $limites,
                'conduccion_fuera_horario' => $conduccionFueraHorario,
                'batch_id' => $batchId,
                'descarga_forzada' => true
            ];

        } catch (\Exception $e) {
            Log::error("Error en obtenerReportesForzado: " . $e->getMessage());
            throw $e;
        }
    }

    private function obtenerReporteConRetry($endpoint, $fechaInicio, $fechaFin, $token, $batchId, $fechaRegistro, $paramExtra = [])
    {
        $url = $this->construirUrl($endpoint, $fechaInicio, $fechaFin, $token, $paramExtra); // MODIFICADO
        $tipoReporte = $this->determinarTipoReporte($endpoint);

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

    private function determinarTipoReporte($endpoint)
    {
        if (strpos($endpoint, 'Excesos') !== false) return 'excesos';
        if (strpos($endpoint, 'Limites') !== false) return 'limites';
        if (strpos($endpoint, 'RP022BodyV05') !== false) return 'conduccion_fuera_horario';
        return 'desconocido';
    }

    private function construirUrl($endpoint, $fechaInicio, $fechaFin, $token, $paramExtra = [])
    {
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);

        $mesi = $inicio->format('y') . $inicio->format('m');
        $mesf = $fin->format('y') . $fin->format('m');

        // Construir parámetros base
        $params = sprintf(
            'E=%s&T=0&IMEI=TODOS&mesi=%s&diai=%s&horai=00&mini=00&mesf=%s&diaf=%s&horaf=23&minf=59',
            $token,
            $mesi,
            $inicio->format('d'),
            $mesf,
            $fin->format('d')
        );

        // AGREGAR parámetros extra AQUÍ
        foreach ($paramExtra as $key => $value) {
            $params .= "&{$key}={$value}";
        }

        // Para reportes de límites y excesos, agregar grupo vacío
        if (strpos($endpoint, 'Limites') !== false || strpos($endpoint, 'Excesos') !== false) {
            $params .= '&grupo=';
        }

        $urlCompleta = $this->baseUrl . $endpoint . '?' . $params;
        Log::info("URL completa generada: " . $urlCompleta);

        return $urlCompleta;
    }

    private function procesarDatos($data, $tipoReporte, $fechaReporte, $batchId, $fechaRegistro)
    {
        $lineas = explode('?', $data);
        $registrosGuardados = 0;
        $registrosDuplicados = 0;

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
                    $resultado = $this->procesarExceso($campos, $batchId, $fechaRegistro);
                } elseif ($tipoReporte === 'limites') {
                    $resultado = $this->procesarLimite($campos, $batchId, $fechaRegistro);
                } elseif ($tipoReporte === 'conduccion_fuera_horario') { // NUEVA CONDICIÓN
                    $resultado = $this->procesarConduccionFueraHorario($campos, $batchId, $fechaRegistro);
                }

                if ($resultado['guardado']) {
                    $registrosGuardados++;
                } else {
                    $registrosDuplicados++;
                }
            } catch (\Exception $e) {
                Log::warning("Error procesando línea {$index}: " . $e->getMessage());
                continue;
            }
        }

        Log::info("Guardados {$registrosGuardados} registros de {$tipoReporte}. Duplicados omitidos: {$registrosDuplicados}");
        return $registrosGuardados;
    }

    private function procesarConduccionFueraHorario($campos, $batchId, $fechaRegistro)
    {
        if (count($campos) < 18) {
            return ['guardado' => false, 'motivo' => 'campos_insuficientes'];
        }

        $placa = trim($campos[1] ?? '');
        $fechaInicio = $this->parsearFechaConduccion($campos[10] ?? null);
        $fechaFin = $this->parsearFechaConduccion($campos[11] ?? null);

        $existe = ConduccionFueraHorario::where('PLACA', $placa)
            ->where('FECHA_INICIO', $fechaInicio)
            ->where('FECHA_FIN', $fechaFin)
            ->exists();

        if ($existe) {
            return ['guardado' => false, 'motivo' => 'duplicado'];
        }

        // Preparar datos para ambas bases de datos
        $datosConduccion = [
            'NOMBRE' => trim($campos[0] ?? ''),
            'PLACA' => $placa,
            'TIPO_VEHICULO' => trim($campos[2] ?? ''),
            'MARCA_VEHICULO' => trim($campos[3] ?? ''),
            'MODELO_VEHICULO' => trim($campos[4] ?? ''),
            'VERSION' => trim($campos[5] ?? ''),
            'GRUPO' => trim($campos[6] ?? ''),
            'SUBGRUPO' => trim($campos[7] ?? ''),
            'ID_CONDUCTOR' => trim($campos[8] ?? ''),
            'NOMBRE_CONDUCTOR' => trim($campos[9] ?? ''),
            'FECHA_INICIO' => $fechaInicio,
            'FECHA_FIN' => $fechaFin,
            'KM_RECORRIDO' => floatval($campos[12] ?? 0),
            'HORAS_TRABAJADAS' => $this->parsearHorasTrabajadas($campos[13] ?? '00:00:00'),
            'DIRECCION_INICIO' => trim($campos[14] ?? ''),
            'DIRECCION_FINAL' => trim($campos[15] ?? ''),
            'UBICACION_INICIO' => trim($campos[16] ?? ''),
            'UBICACION_FINAL' => trim($campos[17] ?? ''),
            'batch_id' => $batchId,
            'file_name' => 'RP022BodyV05.rep',
            'fecha_registro' => $fechaRegistro,
            'final_status' => 'SUCCESS'
        ];

        try {
            // 1. Guardar en BD local
            $conduccionLocal = ConduccionFueraHorario::create($datosConduccion);
            Log::info("Conducción guardada localmente", ['id' => $conduccionLocal->id, 'placa' => $placa]);

            // 2. Intentar guardar en BD externa
            try {
                $datosExterna = $datosConduccion;
                // Convertir fechas para BD externa
                if ($datosExterna['FECHA_INICIO']) {
                    $datosExterna['FECHA_INICIO'] = $datosExterna['FECHA_INICIO']->format('Y-m-d H:i:s');
                }
                if ($datosExterna['FECHA_FIN']) {
                    $datosExterna['FECHA_FIN'] = $datosExterna['FECHA_FIN']->format('Y-m-d H:i:s');
                }
                if ($datosExterna['fecha_registro']) {
                    $datosExterna['fecha_registro'] = $datosExterna['fecha_registro']->format('Y-m-d H:i:s');
                }

                DB::connection('external_db')->table('conduccion_fuera_horario')->insert($datosExterna);
                Log::info("Conducción guardada en BD externa", ['placa' => $placa]);

            } catch (\Exception $e) {
                // Solo logear el error, no fallar el proceso principal
                Log::error("Error guardando en BD externa (continuando): " . $e->getMessage(), ['placa' => $placa]);
            }

            return ['guardado' => true];
        } catch (\Exception $e) {
            Log::error("Error guardando conducción fuera horario: " . $e->getMessage(), [
                'placa' => $placa,
                'error_trace' => $e->getTraceAsString()
            ]);
            return ['guardado' => false, 'motivo' => 'error_bd', 'error' => $e->getMessage()];
        }
    }

    private function parsearFechaConduccion($fechaStr)
    {
        if (empty($fechaStr)) return null;

        try {
            return Carbon::createFromFormat('d/m/Y H:i:s', $fechaStr);
        } catch (\Exception $e) {
            try {
                return Carbon::parse($fechaStr);
            } catch (\Exception $e2) {
                return null;
            }
        }
    }

    private function parsearHorasTrabajadas($horasStr)
    {
        if (empty($horasStr)) return '00:00:00';

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $horasStr)) {
            return $horasStr;
        }

        return '00:00:00';
    }

    private function procesarExceso($campos, $batchId, $fechaRegistro)
    {
        // DEBUG: Log estructura real de datos
        \Log::info('Procesando exceso - total campos: ' . count($campos));
        \Log::info('Campos recibidos:', array_map(function($campo, $index) {
            return "[$index]: " . ($campo ?? 'NULL');
        }, $campos, array_keys($campos)));

        // Mapear campos según estructura real del JSON:
        // 0: PLACA, 1: GRUPO, 2: TIPO VEHICULO, 3: MARCA VEHICULO, 4: MODELO VEHICULO,
        // 5: VERSION, 6: IBUTTON, 7: NOMBRE CONDUCTOR, 8: FECHA_RESTITUCION,
        // 9: UBICACION, 10: DIRECCION, 11: DURACION, 12: VELOCIDAD MAXIMA

        if (count($campos) < 13) {
            \Log::warning('Línea con pocos campos, omitiendo', ['campos' => $campos]);
            return ['guardado' => false, 'motivo' => 'campos_insuficientes'];
        }

        $placa = trim($campos[0] ?? '');
        $grupo = trim($campos[1] ?? '');
        $tipoVehiculo = trim($campos[2] ?? '');
        $marcaVehiculo = trim($campos[3] ?? '');
        $modeloVehiculo = trim($campos[4] ?? '');
        $fechaRestitucion = $this->parsearFecha($campos[8] ?? null);
        $ubicacion = trim($campos[9] ?? '');
        $direccion = trim($campos[10] ?? '');
        $duracion = trim($campos[11] ?? '0');
        $velocidadMaxima = trim($campos[12] ?? '0');

        // USAR FECHA_RESTITUCION como FECHA_EXCESO (o calcular una fecha anterior)
        $fechaExceso = $fechaRestitucion;
        if ($fechaExceso && $duracion > 0) {
            // Calcular fecha exceso restando la duración
            try {
                $fechaExceso = Carbon::parse($fechaRestitucion)->subSeconds(intval($duracion));
            } catch (\Exception $e) {
                $fechaExceso = $fechaRestitucion;
            }
        }

        // Crear descripción combinando campos disponibles
        $descripcion = trim("Exceso de velocidad - $tipoVehiculo $marcaVehiculo $modeloVehiculo");
        if (strlen($descripcion) > 255) {
            $descripcion = substr($descripcion, 0, 255);
        }

        // Procesar duración - convertir a segundos si es necesario
        $duracionSegundos = $this->parsearDuracion($duracion);

        // Procesar velocidad
        $velocidadMaximaInt = intval(floatval($velocidadMaxima));

        \Log::info("Procesando exceso para placa: $placa", [
            'fecha_exceso' => $fechaExceso,
            'fecha_restitucion' => $fechaRestitucion,
            'descripcion' => $descripcion,
            'duracion_seg' => $duracionSegundos,
            'velocidad_maxima' => $velocidadMaximaInt
        ]);

        // Verificar duplicados
        $existe = Exceso::where('PLACA', $placa)
            ->where('FECHA_RESTITUCION', $fechaRestitucion)
            ->where('UBICACION', $ubicacion)
            ->exists();

        if ($existe) {
            \Log::info("Exceso duplicado omitido para placa: $placa");
            return ['guardado' => false, 'motivo' => 'duplicado'];
        }

        try {
            $exceso = Exceso::create([
                'PLACA' => $placa,
                'GRUPO' => $grupo,
                'DESCRIPCION' => $descripcion,
                'FECHA_EXCESO' => $fechaExceso,
                'FECHA_RESTITUCION' => $fechaRestitucion,
                'UBICACION' => $ubicacion,
                'DIRECCION' => $direccion,
                'DURACION_SEG' => $duracionSegundos,
                'VELOCIDAD_MAXIMA' => $velocidadMaximaInt,
                'batch_id' => $batchId,
                'file_name' => 'RP131BodyExcesos.rep',
                'fecha_registro' => $fechaRegistro,
                'final_status' => 'SUCCESS'
            ]);

            \Log::info("Exceso guardado exitosamente", [
                'id' => $exceso->id,
                'placa' => $placa,
                'duracion_seg' => $duracionSegundos,
                'velocidad_maxima' => $velocidadMaximaInt
            ]);

            return ['guardado' => true];

        } catch (\Exception $e) {
            \Log::error("Error guardando exceso para placa $placa: " . $e->getMessage());
            \Log::error("Datos que causaron el error:", [
                'placa' => $placa,
                'grupo' => $grupo,
                'descripcion' => $descripcion,
                'fecha_exceso' => $fechaExceso,
                'fecha_restitucion' => $fechaRestitucion,
                'ubicacion' => $ubicacion,
                'direccion' => $direccion,
                'duracion_seg' => $duracionSegundos,
                'velocidad_maxima' => $velocidadMaximaInt
            ]);
            return ['guardado' => false, 'motivo' => 'error_bd', 'error' => $e->getMessage()];
        }
    }

    /**
     * Método para parsear duración que puede venir en diferentes formatos
     */
    private function parsearDuracion($duracion)
    {
        if (empty($duracion)) return 0;

        // Limpiar el valor
        $duracion = trim($duracion);

        // Si ya es un número, devolverlo
        if (is_numeric($duracion)) {
            return intval($duracion);
        }

        // Si viene en formato HH:MM:SS, convertir a segundos
        if (strpos($duracion, ':') !== false) {
            $partes = explode(':', $duracion);
            if (count($partes) == 3) {
                return (intval($partes[0]) * 3600) + (intval($partes[1]) * 60) + intval($partes[2]);
            }
            if (count($partes) == 2) {
                return (intval($partes[0]) * 60) + intval($partes[1]);
            }
        }

        // Remover cualquier texto y quedarse solo con números
        $soloNumeros = preg_replace('/[^0-9]/', '', $duracion);

        return intval($soloNumeros);
    }

    private function procesarLimite($campos, $batchId, $fechaRegistro)
    {
        $placa = $campos[0] ?? '';
        $fechaAlerta = $this->parsearFecha($campos[3] ?? null);

        // Verificar si ya existe este registro específico
        $existe = Limite::where('PLACA', $placa)
            ->where('FECHA_ALERTA', $fechaAlerta)
            ->where('DESCRIPCION', $campos[2] ?? '')
            ->where('UBICACION', $campos[5] ?? '')
            ->exists();

        if ($existe) {
            return ['guardado' => false, 'motivo' => 'duplicado'];
        }

        Limite::create([
            'PLACA' => $placa,
            'GRUPO' => $campos[1] ?? '',
            'DESCRIPCION' => $campos[2] ?? '',
            'FECHA_ALERTA' => $fechaAlerta,
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

        return ['guardado' => true];
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
            'total_conduccion_fuera_horario' => ConduccionFueraHorario::count(),
            'ultimo_exceso' => Exceso::latest('FECHA_EXCESO')->first(),
            'ultimo_limite' => Limite::latest('FECHA_ALERTA')->first(),
            'ultima_conduccion_fuera_horario' => ConduccionFueraHorario::latest('fecha_registro')->first(),
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

        $batchesConduccion = ConduccionFueraHorario::select('batch_id', 'fecha_registro')
            ->whereNotNull('batch_id')
            ->groupBy('batch_id', 'fecha_registro')
            ->orderBy('fecha_registro', 'desc')
            ->limit(5)
            ->get();

        $todosBatches = $batchesExcesos->merge($batchesLimites)->merge($batchesConduccion)
            ->sortByDesc('fecha_registro')
            ->take(5);

        return $todosBatches->map(function($batch) {
            $excesos = Exceso::where('batch_id', $batch->batch_id)->count();
            $limites = Limite::where('batch_id', $batch->batch_id)->count();
            $conduccion = ConduccionFueraHorario::where('batch_id', $batch->batch_id)->count();
            return [
                'batch_id' => $batch->batch_id,
                'fecha_registro' => $batch->fecha_registro,
                'excesos' => $excesos,
                'limites' => $limites,
                'conduccion_fuera_horario' => $conduccion,
                'total' => $excesos + $limites + $conduccion
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
