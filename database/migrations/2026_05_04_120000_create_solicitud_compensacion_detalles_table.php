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
        Schema::create('solicitud_compensacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_compensacion_id');
            $table->foreignId('compensacion_id');
            $table->decimal('horas_descontadas', 8, 2);
            $table->timestamps();

            $table->foreign('solicitud_compensacion_id', 'scd_solicitud_fk')
                ->references('id')
                ->on('solicitudes_compensaciones')
                ->onDelete('cascade');

            $table->foreign('compensacion_id', 'scd_compensacion_fk')
                ->references('id')
                ->on('compensaciones')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_compensacion_detalles');
    }
};
