<?php

namespace App\Providers;

use App\Console\Commands\ImportGeocercasCommand;
use App\Console\Commands\TrackingCommand;
use Illuminate\Support\ServiceProvider;
use App\Services\BoltrackApiService;
use App\Services\GeocercaService;
use App\Services\DeliveryCalculatorService;
use App\Services\AlertService;
use App\Services\ExcelReportService;

class TruckTrackingServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Registrar servicios como singletons
        $this->app->singleton(BoltrackApiService::class);
        $this->app->singleton(GeocercaService::class);
        $this->app->singleton(DeliveryCalculatorService::class);
        $this->app->singleton(AlertService::class);
        $this->app->singleton(ExcelReportService::class);
    }

    public function boot()
    {
        // Publicar configuración
        $this->publishes([
            __DIR__.'/../../config/truck-tracking.php' => config_path('truck-tracking.php'),
        ], 'truck-tracking-config');

        // Cargar configuración
        $this->mergeConfigFrom(
            __DIR__.'/../../config/truck-tracking.php', 'truck-tracking'
        );
    }
}
