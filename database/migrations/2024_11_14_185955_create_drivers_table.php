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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('placa')->nullable();
            $table->string('cod_sap')->nullable();
            $table->string('cod_fletero')->nullable();
            $table->string('cod_camion')->nullable();
            $table->integer('pallets')->nullable();
            $table->string('grupo')->nullable();
            $table->string('sub_grupo')->nullable();
            $table->string('ol')->nullable();
            $table->string('truck_model')->nullable();
            $table->string('flota')->nullable();
            $table->string('propietario')->nullable();
            $table->string('cel_propietario')->nullable();
            $table->string('conductor')->nullable();
            $table->string('licencia_conductor')->nullable();
            $table->string('celular_conductor')->nullable();
            $table->string('marca')->nullable();
            $table->integer('anio')->nullable();
            $table->string('combustible')->nullable();
            $table->decimal('peso_tracto_ton', 8, 2)->nullable();
            $table->decimal('peso_remolque_ton', 8, 2)->nullable();
            $table->decimal('tara_ton', 8, 2);
            $table->string('argus')->nullable();
            $table->string('config_tracto')->nullable();
            $table->string('config_remolque')->nullable();
            $table->integer('num_llantas')->nullable();
            $table->string('lista_de_flete')->nullable();
            $table->string('planta_resp')->nullable();
            $table->string('nit')->nullable();
            $table->integer('viajes_2021')->default(0)->nullable();
            $table->integer('viajes_2022')->default(0)->nullable();
            $table->integer('viajes_2023')->default(0)->nullable();
            $table->integer('viajes_2024')->default(0)->nullable();
            $table->string('cod_fletero_tms')->nullable();
            $table->integer('anio_ingreso')->nullable();
            $table->string('comentarios')->nullable();
            $table->string('segregacion_de_flota')->nullable();
            $table->string('chofer')->nullable();
            $table->string('camion')->nullable();
            $table->string('columna1')->nullable();
            $table->integer('digitos')->nullable();
            $table->timestamps();
            $table->softDeletes();  // Añadir Soft Deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
