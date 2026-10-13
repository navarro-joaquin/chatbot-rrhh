<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Recrea la vista para que el saldo acumulado coincida con el saldo real:
     * solo restan las solicitudes aprobadas y se añade el saldo cargado o
     * ajustado manualmente, que no tiene un movimiento propio en el historial.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_historial_vacaciones');

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
                WHERE estado = 'aprobado'

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

                UNION ALL

                -- 5. Saldo cargado o ajustado manualmente (diferencia entre el saldo real y los movimientos)
                SELECT
                    v.empleado_id,
                    'Saldo inicial / ajuste manual' as evento,
                    MIN(v.created_at) as fecha,
                    NULL as fecha_reconocida_ingreso,
                    SUM(v.dias_disponibles) - COALESCE(MAX(c.total), 0) + COALESCE(MAX(s.total), 0) as dias,
                    NULL as desde,
                    NULL as hasta,
                    'Saldo registrado sin movimiento propio en el historial' as observacion,
                    0 as registro_id
                FROM vacaciones v
                LEFT JOIN (
                    SELECT empleado_id, SUM(dias_anadidos) as total
                    FROM consolidacion_vacaciones
                    GROUP BY empleado_id
                ) c ON c.empleado_id = v.empleado_id
                LEFT JOIN (
                    SELECT empleado_id, SUM(dias_solicitados) as total
                    FROM solicitudes_vacaciones
                    WHERE estado = 'aprobado'
                    GROUP BY empleado_id
                ) s ON s.empleado_id = v.empleado_id
                GROUP BY v.empleado_id
                HAVING ABS(SUM(v.dias_disponibles) - COALESCE(MAX(c.total), 0) + COALESCE(MAX(s.total), 0)) > 0.00005
            ) as sub_query
        ");
    }

    /**
     * Restaura la definición original de la vista.
     */
    public function down(): void
    {
        (require database_path('migrations/2026_05_06_082255_create_v_historial_vacaciones_view.php'))->up();
    }
};
