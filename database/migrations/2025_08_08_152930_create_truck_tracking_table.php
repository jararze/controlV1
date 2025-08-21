<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('truck_tracking', function (Blueprint $table) {
            $table->id();

            // Datos básicos del viaje
            $table->string('cod')->nullable();
            $table->string('deposito_origen')->nullable();
            $table->string('cod_destino')->nullable();
            $table->string('deposito_destino')->nullable();
            $table->string('planilla')->nullable();
            $table->string('patente')->index();
            $table->date('fecha_salida')->nullable();
            $table->time('hora_salida')->nullable();
            $table->date('fecha_llegada')->nullable();
            $table->time('hora_llegada')->nullable();
            $table->string('cod_producto')->nullable();
            $table->string('producto')->nullable();
            $table->string('status', 50)->nullable()->index();
            $table->integer('salida')->nullable();

            // Datos de ubicación actual
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('velocidad_kmh', 5, 2)->nullable();
            $table->integer('direccion')->nullable();
            $table->string('api_timestamp')->nullable();

            // Estados de geocercas
            $table->string('geocerca_docks')->default('NO')->index();
            $table->string('geocerca_track_trace')->default('NO')->index();
            $table->string('geocerca_cbn')->default('NO')->index();
            $table->string('geocerca_ciudades')->default('NO')->index();

            // Porcentaje de progreso de entrega
            $table->decimal('porcentaje_entrega', 5, 2)->default(0.00)->index();
            $table->string('estado_entrega', 50)->default('EN_TRANSITO');

            // Tiempo de espera para descarga
            $table->datetime('inicio_espera_descarga')->nullable()->index();
            $table->integer('tiempo_espera_minutos')->default(0)->index();
            $table->string('estado_descarga', 50)->default('NO_INICIADO')->index();

            // Control de actualizaciones
            $table->datetime('primera_deteccion')->useCurrent();
            $table->timestamps();

            // Índices únicos
            $table->unique(['patente', 'planilla'], 'unique_patente_planilla');

            // Índices adicionales
            $table->index('fecha_salida');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('truck_tracking');
    }
};
