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
        Schema::create('solicitud_vacacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_vacacion_id')->constrained('solicitudes_vacaciones')->onDelete('cascade');
            $table->foreignId('vacacion_id')->constrained('vacaciones')->onDelete('cascade');
            $table->decimal('dias_descontados', 8, 1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_vacacion_detalles');
    }
};
