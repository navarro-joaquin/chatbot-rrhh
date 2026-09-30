<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Amplía a 4 decimales las columnas de días (vacaciones) y horas
     * (compensaciones) para soportar precisión al minuto.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_historial_vacaciones');

        Schema::table('solicitudes_vacaciones', function (Blueprint $table) {
            $table->decimal('dias_solicitados', 8, 4)->change();
        });

        Schema::table('solicitud_vacacion_detalles', function (Blueprint $table) {
            $table->decimal('dias_descontados', 8, 4)->change();
        });

        Schema::table('consolidacion_vacaciones', function (Blueprint $table) {
            $table->decimal('dias_anadidos', 8, 4)->change();
            $table->decimal('dias_totales_despues', 8, 4)->change();
        });

        Schema::table('vacaciones', function (Blueprint $table) {
            $table->decimal('dias_disponibles', 8, 4)->change();
        });

        Schema::table('compensaciones', function (Blueprint $table) {
            $table->decimal('cantidad_horas', 10, 4)->change();
        });

        Schema::table('solicitudes_compensaciones', function (Blueprint $table) {
            $table->decimal('horas_solicitadas', 8, 4)->change();
        });

        Schema::table('solicitud_compensacion_detalles', function (Blueprint $table) {
            $table->decimal('horas_descontadas', 8, 4)->change();
        });

        $this->recreateHistorialView();
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_historial_vacaciones');

        Schema::table('solicitudes_vacaciones', function (Blueprint $table) {
            $table->decimal('dias_solicitados', 8, 2)->change();
        });

        Schema::table('solicitud_vacacion_detalles', function (Blueprint $table) {
            $table->decimal('dias_descontados', 8, 2)->change();
        });

        Schema::table('consolidacion_vacaciones', function (Blueprint $table) {
            $table->decimal('dias_anadidos', 8, 2)->change();
            $table->decimal('dias_totales_despues', 8, 2)->change();
        });

        Schema::table('vacaciones', function (Blueprint $table) {
            $table->decimal('dias_disponibles', 5, 2)->change();
        });

        Schema::table('compensaciones', function (Blueprint $table) {
            $table->decimal('cantidad_horas', 10, 2)->change();
        });

        Schema::table('solicitudes_compensaciones', function (Blueprint $table) {
            $table->decimal('horas_solicitadas', 8, 2)->change();
        });

        Schema::table('solicitud_compensacion_detalles', function (Blueprint $table) {
            $table->decimal('horas_descontadas', 8, 2)->change();
        });

        $this->recreateHistorialView();
    }

    private function recreateHistorialView(): void
    {
        DB::statement("
            CREATE VIEW v_historial_vacaciones AS
            SELECT
                empleado_id,
                evento,
                fecha,
                fecha_reconocida_ingreso,
                dias,
                SUM(dias) OVER (PARTITION BY empleado_id ORDER BY fecha ASC, registro_id ASC) as saldo,
                desde,
                hasta,
                observacion,
                registro_id
            FROM (
                SELECT
                    empleado_id,
                    'Inicio de contrato' as evento,
                    fecha_inicio as fecha,
                    fecha_inicio as fecha_reconocida_ingreso,
                    0.0 as dias,
                    NULL as desde,
                    NULL as hasta,
                    CONCAT(tipo, ' - ', COALESCE(numero_contrato, 'S/N')) as observacion,
                    id as registro_id
                FROM empleado_contratos

                UNION ALL

                SELECT
                    empleado_id,
                    'Solicitud de vacaciones' as evento,
                    created_at as fecha,
                    NULL as fecha_reconocida_ingreso,
                    -(dias_solicitados) as dias,
                    fecha_inicio as desde,
                    fecha_fin as hasta,
                    motivo as observacion,
                    id as registro_id
                FROM solicitudes_vacaciones
                WHERE estado != 'rechazado'

                UNION ALL

                SELECT
                    empleado_id,
                    'Consolidacion de vacaciones' as evento,
                    created_at as fecha,
                    NULL as fecha_reconocida_ingreso,
                    dias_anadidos as dias,
                    NULL as desde,
                    NULL as hasta,
                    observaciones as observacion,
                    id as registro_id
                FROM consolidacion_vacaciones

                UNION ALL

                SELECT
                    empleado_id,
                    'Reconocimiento de antiguedad' as evento,
                    vigencia_desde as fecha,
                    fecha_reconocida as fecha_reconocida_ingreso,
                    0.0 as dias,
                    NULL as desde,
                    NULL as hasta,
                    observaciones as observacion,
                    id as registro_id
                FROM empleado_antiguedades
            ) as sub_query
        ");
    }
};
