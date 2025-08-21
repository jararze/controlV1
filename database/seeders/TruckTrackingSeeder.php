<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DepositoGeocercaMapping;

class TruckTrackingSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creando mapeos de depósitos a geocercas...');

        // Mapeos basados en la configuración
        $mappings = [
            [
                'deposito_destino' => 'Cerveceria SCZ',
                'ciudad_geocerca' => 'SANTA CRUZ',
                'cbn_geocerca' => 'PLANTA SANTA CRUZ',
                'track_trace_geocerca' => 'TYT - PLANTA SANTA CRUZ',
                'docks_geocerca' => 'DOCK - 7 - PLANTA SANTA CRUZ',
                'activo' => true
            ],
            [
                'deposito_destino' => 'Cerveceria LPZ',
                'ciudad_geocerca' => 'LA PAZ',
                'cbn_geocerca' => 'PLANTA LA PAZ',
                'track_trace_geocerca' => 'TYT - PLANTA LA PAZ',
                'docks_geocerca' => 'DOCK - 3 - PLANTA LA PAZ',
                'activo' => true
            ],
            [
                'deposito_destino' => 'Cerveceria CBBA',
                'ciudad_geocerca' => 'COCHABAMBA',
                'cbn_geocerca' => 'PLANTA COCHABAMBA',
                'track_trace_geocerca' => 'TYT - PLANTA COCHABAMBA',
                'docks_geocerca' => 'DOCK - 5 - PLANTA COCHABAMBA',
                'activo' => true
            ]
        ];

        foreach ($mappings as $mapping) {
            DepositoGeocercaMapping::updateOrCreate(
                ['deposito_destino' => $mapping['deposito_destino']],
                $mapping
            );

            $this->command->line("✅ Mapeo creado: {$mapping['deposito_destino']}");
        }

        $this->command->info('✅ Mapeos de depósitos creados exitosamente');
    }
}
