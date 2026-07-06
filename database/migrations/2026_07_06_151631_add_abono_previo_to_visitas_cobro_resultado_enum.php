<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE visitas_cobro MODIFY resultado ENUM('sin_pago', 'promesa_pago', 'no_encontrado', 'rechazo', 'abono_previo') NOT NULL"
        );
    }

    public function down(): void
    {
        // Antes de achicar el enum, cualquier fila con 'abono_previo' debe reasignarse
        // o la ALTER fallaría (MySQL trunca/rechaza valores fuera del nuevo enum).
        DB::table('visitas_cobro')->where('resultado', 'abono_previo')->update(['resultado' => 'sin_pago']);

        DB::statement(
            "ALTER TABLE visitas_cobro MODIFY resultado ENUM('sin_pago', 'promesa_pago', 'no_encontrado', 'rechazo') NOT NULL"
        );
    }
};
