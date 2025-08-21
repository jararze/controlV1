<?php

namespace App\Console\Commands;

use App\Models\Geocerca;
use App\Services\GeocercaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportGeocercasCommand extends Command
{
    protected $signature = 'truck-tracking:import-geocercas
                           {file : Nombre del archivo Excel en storage/app/truck-tracking/}
                           {--force : Forzar importación sin confirmación}';

    protected $description = 'Importa geocercas desde archivo Excel';

    public function handle(GeocercaService $geocercaService): int
    {
        $filename = $this->argument('file');
        $filePath = storage_path('app/truck-tracking/' . $filename);

        // Verificar que el archivo existe
        if (!file_exists($filePath)) {
            $this->error("❌ Archivo no encontrado: {$filePath}");
            $this->info("💡 Asegúrate de subir el archivo a: storage/app/truck-tracking/{$filename}");
            return 1;
        }

        $this->info("📁 Archivo encontrado: {$filePath}");
        $this->info("📊 Tamaño del archivo: " . $this->formatBytes(filesize($filePath)));

        // Confirmación si no está forzado
        if (!$this->option('force')) {
            if (!$this->confirm('¿Deseas importar las geocercas? Esto puede sobrescribir datos existentes.')) {
                $this->info('Importación cancelada');
                return 0;
            }
        }

        $this->info('🔄 Iniciando importación de geocercas...');
        $this->newLine();

        try {
            // Mostrar progress bar
            $this->withProgressBar(['Analizando archivo...'], function () {
                sleep(1); // Simular análisis
            });
            $this->newLine();

            // Ejecutar importación
            $success = $geocercaService->importFromExcel($filePath);

            if ($success) {
                $this->newLine();
                $this->info('✅ Geocercas importadas exitosamente');

                // Mostrar estadísticas
                $this->showImportStats();

                return 0;
            } else {
                $this->error('❌ Error importando geocercas. Revisa los logs para más detalles.');
                $this->info('💡 Logs disponibles en: storage/logs/laravel.log');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("❌ Error durante importación: {$e->getMessage()}");
            $this->info("💡 Stack trace completo en logs");
            return 1;
        }
    }

    private function showImportStats(): void
    {
        try {
            $stats = Geocerca::selectRaw('nombre_grupo, COUNT(*) as count')
                ->groupBy('nombre_grupo')
                ->pluck('count', 'nombre_grupo');

            $totalGeocercas = Geocerca::count();

            $this->newLine();
            $this->info("📊 ESTADÍSTICAS DE IMPORTACIÓN:");
            $this->info("   📍 Total de geocercas: {$totalGeocercas}");
            $this->newLine();

            foreach ($stats as $grupo => $count) {
                $this->line("   🏷️  {$grupo}: {$count} geocercas");
            }

            $this->newLine();
            $this->info("🎯 Importación completada exitosamente");

        } catch (\Exception $e) {
            $this->warn("⚠️  No se pudieron obtener estadísticas: {$e->getMessage()}");
        }
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
