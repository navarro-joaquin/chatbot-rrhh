<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW v_historial_vacaciones AS
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
                -- 1. Inicio de Contrato
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

                -- 2. Solicitudes de Vacaciones
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

                -- 3. Consolidaciones de Vacaciones
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

                -- 4. Reconocimiento de Antigüedad
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_historial_vacaciones');
    }
};
