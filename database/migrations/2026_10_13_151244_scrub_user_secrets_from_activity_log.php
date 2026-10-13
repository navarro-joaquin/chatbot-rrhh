<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Elimina de la auditoría los datos sensibles de usuarios registrados antes de excluirlos.
     */
    public function up(): void
    {
        $sensibles = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

        DB::table('activity_log')
            ->where('subject_type', (new User)->getMorphClass())
            ->whereNotNull('attribute_changes')
            ->orderBy('id')
            ->chunkById(200, function ($actividades) use ($sensibles): void {
                foreach ($actividades as $actividad) {
                    $cambios = json_decode($actividad->attribute_changes, true);

                    if (! is_array($cambios)) {
                        continue;
                    }

                    foreach (['attributes', 'old'] as $grupo) {
                        foreach ($sensibles as $campo) {
                            unset($cambios[$grupo][$campo]);
                        }
                    }

                    DB::table('activity_log')
                        ->where('id', $actividad->id)
                        ->update(['attribute_changes' => json_encode($cambios)]);
                }
            });
    }

    /**
     * Los datos eliminados no se pueden recuperar.
     */
    public function down(): void
    {
        //
    }
};
