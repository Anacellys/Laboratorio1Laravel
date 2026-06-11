<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las tablas base de autenticacion.
     */
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            // Campos solicitados para el laboratorio de autenticacion con 2FA.
            $table->id('id_usuario');
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('correo')->unique();
            $table->string('HashMagic');
            $table->enum('sexo', ['Masculino', 'Femenino', 'Otro']);
            $table->string('secret_2fa')->comment('Secret Base32 para Google Authenticator');
            $table->boolean('two_factor_enabled')->default(false);
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            // Laravel usa esta tabla si se habilita recuperacion de contrasena.
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            // Tabla para manejar sesiones PHP/Laravel en MySQL.
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Elimina las tablas creadas por esta migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
