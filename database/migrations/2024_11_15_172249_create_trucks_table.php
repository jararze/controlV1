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
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->string('cod')->nullable();
            $table->string('deposito_origen')->nullable();
            $table->string('cod_destino')->nullable();
            $table->string('deposito_destino')->nullable();
            $table->string('planilla')->nullable();
            $table->string('flete')->nullable();
            $table->string('nombre_fletero')->nullable();
            $table->string('camion')->nullable();
            $table->string('patente')->nullable();
            $table->date('fecha_salida')->nullable();
            $table->time('hora_salida')->nullable();
            $table->date('fecha_llegada')->nullable();
            $table->time('hora_llegada')->nullable();
            $table->decimal('diferencia_horas', 10, 2)->nullable();
            $table->string('distancia')->nullable();
            $table->string('categoria_flete')->nullable();
            $table->string('cierre')->nullable();
            $table->string('status')->nullable();
            $table->string('puntaje')->nullable();
            $table->integer('tarifa')->nullable();
            $table->string('cod_producto')->nullable();
            $table->string('producto')->nullable();
            $table->integer('salida')->nullable();
            $table->integer('entrada')->nullable();
            $table->decimal('valor_producto', 10, 2)->nullable();
            $table->string('variedad')->nullable();
            $table->string('linea')->nullable();
            $table->string('tipo')->nullable();
            $table->string('numero_orden')->nullable();
            $table->date('fecha_orden')->nullable();
            $table->uuid('batch_id')->nullable();                     // Batch ID
            $table->string('file_name', 255)->nullable();
            $table->dateTime('fecha_registro')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->integer('final_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};
