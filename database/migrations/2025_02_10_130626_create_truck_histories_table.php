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
        Schema::create('truck_histories', function (Blueprint $table) {
            // Campos clave para búsquedas
            $table->string('planilla')->index();
            $table->string('patente')->index();
            $table->string('cod_producto')->index();
            $table->date('fecha_salida')->index();
            $table->uuid('batch_id')->index();

            // Datos completos en JSON
            $table->jsonb('original_data');

            // Metadatos del cambio
            $table->string('change_type')->comment('UPDATE, DELETE');
            $table->timestamp('changed_at')->index();
            $table->timestamps();

            // Índice compuesto para búsquedas comunes
            $table->index(['planilla', 'patente']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('truck_histories');
    }
};
