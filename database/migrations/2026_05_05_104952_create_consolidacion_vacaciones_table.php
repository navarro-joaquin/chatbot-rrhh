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
        Schema::create('consolidacion_vacaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->foreignId('gestion_id')->constrained('gestiones')->cascadeOnDelete();
            $table->decimal('dias_anadidos', 8, 1);
            $table->decimal('dias_totales_despues', 8, 1);
            $table->string('origen'); // e.g., 'aniversario', 'proteccion'
            $table->string('accion'); // 'creada' o 'actualizada'
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consolidacion_vacaciones');
    }
};
