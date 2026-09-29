<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El codigo_anterior auto-generado (para clientes sin código heredado
     * real) había quedado igualado al "codigo" interno, que salta según
     * la posición del cliente entre TODOS los clientes (miles) -- por eso
     * se veían números dispersos como 13890 en vez de una lista compacta.
     *
     * Esta migración renumera SOLO el codigo_anterior de esos clientes
     * (los que tienen codigo_anterior numérico >=10000, que solo puede
     * venir de ese auto-copiado, nunca de un código legado real) para que
     * queden consecutivos desde 10,001, sin tocar el "codigo" interno ni
     * los códigos legado reales (<10000).
     */
    public function up(): void
    {
        $ids = DB::table('clientes')
            ->whereRaw('codigo_anterior REGEXP "^[0-9]+$"')
            ->whereRaw('CAST(codigo_anterior AS UNSIGNED) >= 10000')
            ->orderBy('id')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($ids) {
            foreach ($ids as $posicion => $id) {
                DB::table('clientes')->where('id', $id)->update([
                    'codigo_anterior' => (string) (10001 + $posicion),
                ]);
            }
        });
    }

    public function down(): void
    {
        // No se revierte: el valor original ya no se puede reconstruir.
    }
};
