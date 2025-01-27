<?php

namespace App\Http\Controllers;

use App\Models\BolTrackHeader;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class BoltrackUpdateController extends Controller
{
    public function update()
    {
        $client = new Client();

        try {
            // Make a GET request with the token header
            $response = $client->request('GET',
                'https://gestiondeflota.boltrack.net/integracionapi/ultimaubicaciontodos', [
                    'headers' => [
                        'token' => 'bltrck2021_454fd3d', // Add token header
                    ],
                ]);

            // Decode the response
            $data = json_decode($response->getBody(), true);
            dd("dsa");
            if (is_array($data)) {
                // Update or create a header entry
                $header = BolTrackHeader::updateOrCreate(
//                    ['id' => 1], // Assuming only one header entry is needed
                    ['estatus' => 'Actualizado', 'hora_actualizacion' => now()]
                );

                foreach ($data as $item) {
                    $formattedFecha = Carbon::parse($item['fecha'])->format('Y-m-d H:i:s');

                    // Create or update each body (Detalle) and associate it with the header
                    $header->bodies()->updateOrCreate(
                        ['id_unidad' => $item['id_unidad']], // Unique identifier for each body
                        [
                            'nombre' => $item['nombre'],
                            'fecha' => $formattedFecha, // Use the converted datetime
                            'latitud' => $item['latitud'],
                            'longitud' => $item['longitud'],
                            'motor_encendido' => $item['motor_encendido'],
                            'velocidad_kmh' => $item['velocidad_kmh'],
                            'direccion' => $item['direccion'],
                            'odometro_dia_m' => $item['odometro_dia_m'],
                            'altura' => $item['altura'],
                            'tiempo_encendido' => $item['tiempoEncedido'],
                            'tiempo_ralenti' => $item['tiempoRalenti'],
                            'tiempo_movimiento' => $item['tiempoMovimientoFormated'],
                        ]
                    );
                }
                return response()->json(['message' => 'Tablas actualizadas correctamente.'], 200);
            } else {
                return response()->json(['message' => 'Error al conectar con la API.'], 500);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
