<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las compensaciones pasan a pertenecer solo al empleado: sobreviven al fin de un
 * contrato y se siguen usando con el siguiente.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('compensaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contrato_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * El vínculo original no se puede recuperar, por eso la columna vuelve como opcional.
     */
    public function down(): void
    {
        Schema::table('compensaciones', function (Blueprint $table) {
            $table->foreignId('contrato_id')->nullable()->after('gestion_id')
                ->constrained('empleado_contratos')->nullOnDelete();
        });
    }
};
