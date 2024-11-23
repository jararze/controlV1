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
        Schema::create('upload_matrix', function (Blueprint $table) {
            $table->id();
            $table->integer('cod_origen')->nullable();                // Cod Origen
            $table->string('dep_origen', 50)->nullable();             // Dep Origen
            $table->integer('cod_destino')->nullable();               // Cod Des
            $table->string('dep_destino', 50)->nullable();            // Dep Des
            $table->string('planilla', 50)->nullable();               // Planilla
            $table->string('nombre_fletero', 100)->nullable();        // Nombre Fletero
            $table->string('cod_cam', 50)->nullable();                // Cod Cam
            $table->string('patente', 50)->nullable();                // Patente
            $table->dateTime('salida')->nullable();                   // Salida
            $table->string('columna1', 50)->nullable();               // Columna1
            $table->string('status', 50)->nullable();                 // Status
            $table->string('cod_prod', 50)->nullable();               // Cod Prod
            $table->string('producto', 255)->nullable();              // Producto
            $table->integer('bultos')->nullable();                    // Bultos
            $table->string('tipo_producto', 50)->nullable();          // Tipo Prod
            $table->string('tipo_viaje', 50)->nullable();             // Tipo Viaje
            $table->integer('hl')->nullable();                        // HL
            $table->string('referencia', 255)->nullable();            // Referencia
            $table->string('eta')->nullable();                      // ETA
            $table->string('obs_eta', 255)->nullable();               // Obs ETA
            $table->string('placa_real', 255)->nullable();            // Placa Real
            $table->string('eta_observacion', 255)->nullable();       // ETA + Observación
            $table->string('comparacion_eta', 255)->nullable();       // Comparación ETA
            $table->string('comparacion_obs_eta', 255)->nullable();   // Comparación Obs ETA
            $table->string('gps', 255)->nullable();                    // GPS
            $table->string('coordenadas', 255)->nullable();           // Coordenadas
            $table->dateTime('ultimo_reporte_gps')->nullable();       // Último reporte GPS
            $table->string('ruta', 255)->nullable();                  // Ruta
            $table->string('sla_dias', 50)->nullable();               // SLA (días)
            $table->string('tgt', 50)->nullable();                    // TGT
            $table->string('tmv_vs_sla', 50)->nullable();             // TMV vs SLA
            $table->string('duplicado', 50)->nullable();              // Duplicado
            $table->string('sku_agrupado', 100)->nullable();          // SKU Agrupado
            $table->string('marca', 100)->nullable();                 // Marca
            $table->string('calibre', 100)->nullable();               // Calibre
            $table->string('clase', 100)->nullable();                 // Clase
            $table->uuid('batch_id')->nullable();                     // Batch ID
            $table->string('file_name', 255)->nullable();
            $table->dateTime('fecha_registro')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_matrix');
    }
};
