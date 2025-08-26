<?php

namespace App\Http\Controllers;

use App\Services\ReporteFlotaService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class ReporteFlotaController extends Controller
{
    private $reporteService;

    public function __construct(ReporteFlotaService $reporteService)
    {
        $this->reporteService = $reporteService;
    }

    public function index()
    {
        $estadisticas = $this->reporteService->obtenerEstadisticas();
        return view('reportes.index', compact('estadisticas'));
    }

    /**
     * Contar registros de una fecha específica (para cajita "Hoy")
     */
    public function contarRegistrosFecha(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Fecha inválida'
            ], 422);
        }

        try {
            $fecha = Carbon::parse($request->input('fecha'));
            $fechaInicio = $fecha->startOfDay();
            $fechaFin = $fecha->copy()->endOfDay();

            // Contar excesos del día
            $excesosHoy = Exceso::whereBetween('FECHA_EXCESO', [$fechaInicio, $fechaFin])->count();

            // Contar límites del día
            $limitesHoy = Limite::whereBetween('FECHA_ALERTA', [$fechaInicio, $fechaFin])->count();

            // Contar conducción fuera horario del día
            $conduccionHoy = ConduccionFueraHorario::whereBetween('FECHA_INICIO', [$fechaInicio, $fechaFin])->count();

            $total = $excesosHoy + $limitesHoy + $conduccionHoy;

            return response()->json([
                'success' => true,
                'excesos' => $excesosHoy,
                'limites' => $limitesHoy,
                'conduccion_fuera_horario' => $conduccionHoy,
                'total' => $total,
                'fecha' => $fecha->format('Y-m-d')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al contar registros: ' . $e->getMessage()
            ], 500);
        }
    }

    public function obtenerReportes(Request $request)
    {
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ignore_user_abort(true);

        // DEBUG: Log todos los datos recibidos
//        \Log::info('=== DEBUG OBTENER REPORTES ===');
//        \Log::info('Todos los datos del request:', $request->all());
//        \Log::info('Método HTTP: ' . $request->method());
//        \Log::info('Content-Type: ' . ($request->header('Content-Type') ?? 'N/A'));

        $validator = Validator::make($request->all(), [
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'token' => 'nullable|string',
            'forzar' => 'nullable|in:0,1,true,false' // Aceptar tanto string como boolean
        ]);

        // DEBUG: Mostrar errores de validación específicos
        if ($validator->fails()) {
//            \Log::error('Errores de validación:', $validator->errors()->toArray());
//            \Log::error('Datos que fallaron la validación:', $request->all());

            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors(),
                'debug_data' => $request->all() // Para debug
            ], 422);
        }

        // DEBUG: Log datos validados
//        \Log::info('Datos validados correctamente:', $validator->validated());

        try {
            $token = $request->input('token');
            // CORRECCIÓN: Manejar forzar como string o boolean
            $forzarRaw = $request->input('forzar');
            $forzar = in_array($forzarRaw, ['1', 'true', true, 1], true);

//            \Log::info('Parámetros procesados:');
//            \Log::info('- Token proporcionado: ' . ($token ? 'SÍ (length: ' . strlen($token) . ')' : 'NO'));
//            \Log::info('- Forzar raw: ' . ($forzarRaw ?? 'null') . ' (tipo: ' . gettype($forzarRaw) . ')');
//            \Log::info('- Forzar procesado: ' . ($forzar ? 'SÍ' : 'NO'));
//            \Log::info('- Fecha inicio: ' . $request->input('fecha_inicio'));
//            \Log::info('- Fecha fin: ' . $request->input('fecha_fin'));

            // Si hay un nuevo token, guardarlo
            if ($token && $token !== $this->reporteService->obtenerTokenActivo()) {
                \Log::info('Guardando nuevo token');
                $this->reporteService->guardarToken($token);
            }

            // NUEVA LÓGICA: Decidir si usar el método normal o forzado
            if ($forzar) {
                \Log::info('Descarga forzada - ignorando duplicados');
                $resultados = $this->reporteService->obtenerReportesForzado(
                    $request->input('fecha_inicio'),
                    $request->input('fecha_fin'),
                    $token
                );

                $totalRegistros = $resultados['excesos'] + $resultados['limites'];

                \Log::info('Descarga forzada completada:', $resultados);

                return response()->json([
                    'success' => true,
                    'message' => "Descarga forzada completada. Total de registros: {$totalRegistros}",
                    'data' => $resultados,
                    'forzada' => true
                ]);

            } else {
                // Obtener reportes usando el servicio normal (con verificación de duplicados)
                \Log::info('Iniciando descarga normal con verificación de duplicados');

                $resultados = $this->reporteService->obtenerReportes(
                    $request->input('fecha_inicio'),
                    $request->input('fecha_fin'),
                    $token
                );

                \Log::info('Resultado de descarga normal:', $resultados);

                // NUEVA VERIFICACIÓN: Si se detectaron duplicados
                if (isset($resultados['duplicados_detectados']) && $resultados['duplicados_detectados']) {
                    \Log::warning('Duplicados detectados, enviando respuesta 409');

                    return response()->json([
                        'success' => false,
                        'duplicados_detectados' => true,
                        'message' => $resultados['mensaje_duplicados'],
                        'detalles_duplicados' => $resultados['detalles_duplicados'],
                        'mostrar_opcion_forzar' => true
                    ], 409); // 409 Conflict
                }

                $totalRegistros = $resultados['excesos'] + $resultados['limites'];

                return response()->json([
                    'success' => true,
                    'message' => "Reportes obtenidos exitosamente. Total de registros: {$totalRegistros}",
                    'data' => $resultados
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Error en obtenerReportes:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            // Identificar tipos específicos de error
            if (str_contains($e->getMessage(), 'token') || str_contains($e->getMessage(), 'unauthorized')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido o expirado. Actualiza el token desde el sistema de flota.'
                ], 401);
            }

            if (str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'timed out')) {
                return response()->json([
                    'success' => false,
                    'message' => 'El servidor de reportes está tardando mucho en responder. Intenta nuevamente en unos minutos.'
                ], 408);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reportes: ' . $e->getMessage(),
                'debug_trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * NUEVO MÉTODO: Verificar duplicados sin descargar
     */
    public function verificarDuplicados(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Fechas inválidas',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $verificacion = $this->reporteService->verificarDuplicados(
                $request->input('fecha_inicio'),
                $request->input('fecha_fin')
            );

            return response()->json([
                'success' => true,
                'tiene_duplicados' => $verificacion['tiene_duplicados'],
                'mensaje' => $verificacion['mensaje'],
                'detalles' => $verificacion['detalles']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar duplicados: ' . $e->getMessage()
            ], 500);
        }
    }

    public function actualizarToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'fecha_expiracion' => 'nullable|date|after:now'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $token = $request->input('token');
            $fechaExpiracion = $request->input('fecha_expiracion')
                ? Carbon::parse($request->input('fecha_expiracion'))
                : null;

            $this->reporteService->guardarToken($token, $fechaExpiracion);

            return response()->json([
                'success' => true,
                'message' => 'Token actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar token: ' . $e->getMessage()
            ], 500);
        }
    }

    public function validarToken(Request $request)
    {
        $token = $request->input('token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token requerido'
            ], 400);
        }

        try {
            $esValido = $this->reporteService->validarToken($token);

            return response()->json([
                'success' => true,
                'valido' => $esValido,
                'message' => $esValido ? 'Token válido' : 'Token inválido o expirado'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar token: ' . $e->getMessage()
            ], 500);
        }
    }

    public function obtenerUltimoReporte()
    {
        try {
            $estadisticas = $this->reporteService->obtenerEstadisticas();

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function procesarManual(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tipo' => 'required|in:excesos,limites',
                'data' => 'required|string',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos'
                ], 422);
            }

            // DEBUG: Analizar estructura de datos antes de procesar
            $analisis = $this->reporteService->analizarEstructuraDatos(
                $request->input('data'),
                $request->input('tipo')
            );

            \Log::info('Análisis de estructura:', $analisis);

            $batchId = \Str::uuid();
            $fechaRegistro = Carbon::now();

            $registros = $this->reporteService->procesarDatosManual(
                $request->input('data'),
                $request->input('tipo'),
                $request->input('fecha_inicio'),
                $batchId,
                $fechaRegistro
            );

            return response()->json([
                'success' => true,
                'registros' => $registros,
                'batch_id' => $batchId,
                'message' => "Procesados {$registros} registros de {$request->input('tipo')}",
                'debug_analisis' => $analisis
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en procesarManual:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
