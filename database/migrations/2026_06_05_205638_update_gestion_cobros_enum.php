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
        // MODIFY ... ENUM es sintaxis exclusiva de MySQL. En SQLite (tests en
        // memoria) el enum ya se crea como varchar sin restricción, así que no
        // hay nada que alterar.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE gestion_cobros MODIFY estado ENUM('pendiente', 'parcialmente_cobrado', 'cobrado', 'vencido') DEFAULT 'pendiente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE gestion_cobros MODIFY estado ENUM('pendiente', 'cobrado', 'vencido') DEFAULT 'pendiente'");
    }
};
