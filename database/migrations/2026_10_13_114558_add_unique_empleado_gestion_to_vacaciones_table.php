<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duplicados = DB::table('vacaciones')
            ->select('empleado_id', 'gestion_id')
            ->groupBy('empleado_id', 'gestion_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicados > 0) {
            throw new RuntimeException(
                "Existen {$duplicados} combinaciones empleado/gestión duplicadas en vacaciones. Unifíquelas antes de migrar."
            );
        }

        Schema::table('vacaciones', function (Blueprint $table) {
            $table->unique(['empleado_id', 'gestion_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacaciones', function (Blueprint $table) {
            // MySQL puede haber reemplazado el índice de la llave foránea por el índice único.
            $table->index('empleado_id');
            $table->dropUnique(['empleado_id', 'gestion_id']);
        });
    }
};
