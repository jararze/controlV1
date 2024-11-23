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
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_call_id')->constrained('batch_calls')->onDelete('cascade'); // Relación con batch_calls
            $table->text('note')->nullable(); // Observación de la llamada
            $table->enum('destino', ['si', 'no'])->default('no'); // En destino
            $table->enum('descargo', ['si', 'no', '--'])->default('--'); // Descargo
            $table->integer('tiempo_espera')->default(0); // Tiempo de espera
            $table->enum('llegara_en_horario', ['si', 'no'])->default('si'); // Llegará en horario
            $table->integer('fuera_de_horario')->default(0); // Cuán fuera de horario
            $table->enum('diesel', ['si', 'no'])->default('no'); // Diesel
            $table->enum('fila', ['si', 'no', 'ira'])->default('no'); // En fila
            $table->enum('falla_mecanica', ['si', 'no'])->default('no'); // Falla mecánica
            $table->enum('bloqueo', ['si', 'no'])->default('no'); // En bloqueo
            $table->timestamps(); // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
