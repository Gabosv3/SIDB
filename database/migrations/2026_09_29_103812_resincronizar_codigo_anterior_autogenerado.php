<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * La migración anterior (renumerar_codigo_contiguo_desde_10000) cambió
     * el "codigo" interno de todos los clientes >=10000, pero no tocó
     * "codigo_anterior" -- así que los clientes cuyo codigo_anterior había
     * sido auto-copiado del codigo (porque no tenían código heredado real)
     * quedaron con un codigo_anterior VIEJO, desincronizado del codigo
     * actual.
     *
     * Un codigo_anterior puramente numérico y >=10000 SOLO puede venir de
     * ese auto-copiado (los códigos legado reales del sistema viejo son
     * siempre menores a 10000), así que es seguro re-sincronizarlos aquí
     * con el codigo actual de cada cliente.
     */
    public function up(): void
    {
        DB::table('clientes')
            ->whereRaw('codigo_anterior REGEXP "^[0-9]+$"')
            ->whereRaw('CAST(codigo_anterior AS UNSIGNED) >= 10000')
            ->update([
                'codigo_anterior' => DB::raw('CAST(codigo AS CHAR)'),
            ]);
    }

    public function down(): void
    {
        // No se revierte: no hay forma de recuperar el valor viejo desincronizado.
    }
};
