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
        Schema::create('empleado_antiguedades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            $table->foreignId('contrato_id')->nullable()->constrained('empleado_contratos')->nullOnDelete();
            $table->date('fecha_reconocida');
            $table->enum('origen', ['Contrato', 'Regularizacion', 'Resolucion Manual'])->default('Contrato');
            $table->string('observaciones')->nullable();
            $table->boolean('vigente')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleado_antiguedades');
    }
};
