<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\JobStatusChecker;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // Registrar JobStatusChecker como singleton
        $this->app->singleton(JobStatusChecker::class, function ($app) {
            return new JobStatusChecker();
        });

        // Cualquier otro código que ya tengas aquí...
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
