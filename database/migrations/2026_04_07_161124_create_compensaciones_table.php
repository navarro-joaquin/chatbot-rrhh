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
        Schema::create('compensaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            $table->foreignId('gestion_id')->constrained('gestiones')->onDelete('cascade');
            $table->foreignId('contrato_id')->constrained('empleado_contratos')->onDelete('cascade');
            $table->decimal('cantidad_horas', 10, 2)->default(0);
            $table->string('descripcion')->nullable();
            $table->date('fecha_registro')->nullable();
            $table->enum('estado', ['disponible', 'utilizado', 'vencido'])->default('disponible');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compensaciones');
    }
};
