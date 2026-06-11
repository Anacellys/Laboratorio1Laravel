<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Esta migracion queda por compatibilidad con el proyecto original.
     * Los campos 2FA se crean directamente en la tabla usuarios.
     */
    public function up(): void
    {
        //
    }

    /**
     * No hay cambios que revertir en esta migracion de compatibilidad.
     */
    public function down(): void
    {
        //
    }
};
