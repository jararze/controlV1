<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTruckTracking;
use Illuminate\Console\Command;

class TrackingCommand extends Command
{
    protected $signature = 'truck-tracking:process
                           {--continuous : Ejecutar en modo continuo}
                           {--interval=4 : Intervalo en horas para modo continuo}';

    protected $description = 'Procesa el tracking de camiones';

    public function handle(): int
    {
        if ($this->option('continuous')) {
            return $this->handleContinuous();
        }

        return $this->handleSingle();
    }

    private function handleSingle(): int
    {
        $this->info('Iniciando procesamiento único de truck tracking...');

        try {
            ProcessTruckTracking::dispatchSync();
            $this->info('✅ Procesamiento completado exitosamente');
            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Error en procesamiento: {$e->getMessage()}");
            return 1;
        }
    }

    private function handleContinuous(): int
    {
        $intervalHours = (int) $this->option('interval');
        $intervalSeconds = $intervalHours * 3600;

        $this->info("Iniciando monitoreo continuo cada {$intervalHours} horas...");
        $this->info('Presiona Ctrl+C para detener');

        while (true) {
            try {
                $this->info("\n" . now()->format('Y-m-d H:i:s') . " - Ejecutando ciclo de monitoreo...");

                ProcessTruckTracking::dispatchSync();

                $this->info("✅ Ciclo completado. Esperando {$intervalHours} horas...");
                sleep($intervalSeconds);

            } catch (\Exception $e) {
                $this->error("❌ Error en ciclo: {$e->getMessage()}");
                $this->info("Esperando 30 minutos antes de reintentar...");
                sleep(1800); // 30 minutos
            }
        }
    }
}
