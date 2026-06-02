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
        Schema::table('users', function (Blueprint $table) {
            // Columnas para autenticación de dos factores (2FA)
            $table->string('two_factor_secret')->nullable()->after('password')->comment('Secret TOTP para Google Authenticator');
            $table->boolean('two_factor_enabled')->default(false)->after('two_factor_secret')->comment('Indica si 2FA está habilitado para el usuario');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_enabled')->comment('Fecha cuando el usuario confirmó 2FA');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar las columnas de 2FA
            $table->dropColumn(['two_factor_secret', 'two_factor_enabled', 'two_factor_confirmed_at']);
        });
    }
};
