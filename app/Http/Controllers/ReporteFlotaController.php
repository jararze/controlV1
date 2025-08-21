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

    public function obtenerReportes(Request $request)
    {
        set_time_limit(0); // Sin límite de tiempo
        ini_set('max_execution_time', 0);
        ignore_user_abort(true);

        $validator = Validator::make($request->all(), [
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'token' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $token = $request->input('token');

            // Si hay un nuevo token, guardarlo
            if ($token && $token !== $this->reporteService->obtenerTokenActivo()) {
                \Log::info('Guardando nuevo token');
                $this->reporteService->guardarToken($token);
            }

            // Obtener reportes usando el servicio
            $resultados = $this->reporteService->obtenerReportes(
                $request->input('fecha_inicio'),
                $request->input('fecha_fin'),
                $token
            );

            $totalRegistros = $resultados['excesos'] + $resultados['limites'];

            return response()->json([
                'success' => true,
                'message' => "Reportes obtenidos exitosamente. Total de registros: {$totalRegistros}",
                'data' => $resultados
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en obtenerReportes:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
                'message' => 'Error al obtener reportes: ' . $e->getMessage()
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
}
