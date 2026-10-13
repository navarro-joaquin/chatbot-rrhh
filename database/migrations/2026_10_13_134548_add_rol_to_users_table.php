<?php

use App\Enums\Rol;
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
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol', 20)->default(Rol::RecursosHumanos->value)->after('email');
        });

        // Los usuarios existentes tenían acceso total; lo conservan, salvo el usuario técnico del bot.
        DB::table('users')
            ->where('email', '!=', (string) config('services.rrhh_bot.user_email', ''))
            ->update(['rol' => Rol::Administrador->value]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rol');
        });
    }
};
