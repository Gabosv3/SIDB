<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // 'manual' es el default para lo que se cree de aquí en adelante
            // por el formulario del sistema. Los productos que ya existían al
            // momento de esta migración vinieron todos de importaciones de
            // Excel (confirmado con el dueño del negocio), así que se marcan
            // como 'excel' abajo -- de ahí en adelante ambos grupos quedan
            // separados para poder limpiar duplicados solo en el lado de Excel
            // sin tocar lo que ya se cargó bien desde el sistema.
            $table->enum('origen', ['excel', 'manual'])->default('manual')->after('codigo');
        });

        DB::table('productos')->update(['origen' => 'excel']);
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('origen');
        });
    }
};
