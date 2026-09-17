<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nombre que se muestra en la app (tickets, saludo, menú) en vez
            // del nombre completo real, cuando se define. No reemplaza el
            // "name" real (el de la cuenta/login) — solo se usa para lo que
            // la app le muestra al cliente/al propio usuario.
            $table->string('alias')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('alias');
        });
    }
};
