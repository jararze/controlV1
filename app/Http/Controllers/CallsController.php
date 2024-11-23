<?php

namespace App\Http\Controllers;

use App\Models\BatchCall;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CallsController extends Controller
{
    public function registerCall(Request $request, $patente = null)
    {
//        $id = $request->get('id'); // Obtener el parámetro 'id'
//        $extraParam = $request->get('extraParam'); // Obtener otro parámetro
//
//         Realiza tu lógica con los parámetros
//        $record = Matrix::find($id);
//
//        if ($record) {
//            return response()->json([
//                'name' => $record->driver ? $record->driver->conductor : 'No disponible',
//                'client' => $record->client ?? 'Sin cliente',
//                'phone' => $record->driver ? $record->driver->celular_conductor : 'No disponible',
//                'details' => $record->obs_eta ?? 'Sin observaciones'
//            ]);
//        }

        return response()->json(['error' => 'Registro no encontrado'], 404);
    }

    public function saveCall(Request $request)
    {
        try {
            // Validar los datos del formulario
            $data = $request->validate([
                // Datos de la cabecera
                'planilla' => 'required|string',
                'batchid' => 'required|string',

                // Datos del cuerpo (CallLog)
                'note' => 'nullable|string',
                'destino' => 'required|in:si,no',
                'descargo' => 'required|in:si,no,--',
                'espera' => 'nullable|integer|min:0',
                'llegara_en_horario' => 'required|in:si,no',
                'fuera_hora' => 'nullable|integer|min:0',
                'diesel' => 'required|in:si,no',
                'fila' => 'required|in:si,no,ira',
                'falla_mecanica' => 'required|in:si,no',
                'bloqueo' => 'required|in:si,no',
            ]);

            // Guardar o encontrar la cabecera (BatchCall)
            $batchCall = BatchCall::firstOrCreate(
                ['planilla' => $data['planilla'], 'batch_id' => $data['batchid']]
            );

            // Crear un nuevo registro de llamada (CallLog)
            $callLog = $batchCall->callLogs()->create([
                'note' => $data['note'],
                'destino' => $data['destino'],
                'descargo' => $data['descargo'],
                'tiempo_espera' => $data['espera'],
                'llegara_en_horario' => $data['llegara_en_horario'],
                'fuera_de_horario' => $data['fuera_hora'],
                'diesel' => $data['diesel'],
                'fila' => $data['fila'],
                'falla_mecanica' => $data['falla_mecanica'],
                'bloqueo' => $data['bloqueo'],
            ]);

            // Respuesta de éxito
            return response()->json([
                'message' => 'Registro guardado correctamente.',
                'batch_call' => $batchCall,
                'call_log' => $callLog,
            ], 201);

        } catch (ValidationException $e) {
            // Capturar errores de validación
            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $e->errors(), // Devolver errores específicos de validación
            ], 422);
        } catch (Exception $e) {
            Log::error('Error inesperado en saveCall:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Capturar cualquier otro error
            return response()->json([
                'message' => 'Error inesperado.',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

}
