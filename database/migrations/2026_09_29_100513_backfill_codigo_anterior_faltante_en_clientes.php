<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rellena codigo_anterior con el mismo valor de "codigo" (el
     * correlativo interno, único) para los clientes que nunca tuvieron un
     * código heredado del sistema viejo -- así dejan de aparecer como
     * "Sin código" esperando que alguien lo escriba a mano.
     */
    public function up(): void
    {
        DB::table('clientes')
            ->whereNull('codigo_anterior')
            ->whereNotNull('codigo')
            ->update([
                'codigo_anterior' => DB::raw('CAST(codigo AS CHAR)'),
            ]);
    }

    public function down(): void
    {
        // No se revierte: no hay forma de distinguir un codigo_anterior
        // que ya existía de uno que esta migración generó.
    }
};
