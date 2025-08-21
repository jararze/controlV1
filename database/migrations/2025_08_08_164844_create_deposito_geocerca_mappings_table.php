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

        Schema::dropIfExists('deposito_geocerca_mappings');


        Schema::create('deposito_geocerca_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('deposito_destino')->unique()->comment('Nombre del depósito destino');
            $table->string('ciudad_geocerca')->nullable()->comment('Nombre de geocerca de ciudad');
            $table->string('cbn_geocerca')->nullable()->comment('Nombre de geocerca CBN');
            $table->string('track_trace_geocerca')->nullable()->comment('Nombre de geocerca Track & Trace');
            $table->string('docks_geocerca')->nullable()->comment('Nombre de geocerca Docks');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Índices únicos
            $table->index('activo', 'idx_deposito_mapping_activo');
            $table->index('deposito_destino', 'idx_deposito_mapping_destino');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposito_geocerca_mappings');
    }
};
