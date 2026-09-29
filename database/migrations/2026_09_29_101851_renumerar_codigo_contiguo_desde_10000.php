<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Renumera el "codigo" interno de todos los clientes con codigo>=10000
     * para que quede consecutivo desde 10000, sin huecos (los huecos eran
     * de clientes de prueba eliminados durante el desarrollo). Se respeta
     * el orden que ya tenían por codigo. Los clientes activos (no
     * eliminados) reciben el bloque más bajo y consecutivo; los
     * eliminados (soft-delete) se recorren después, para que no estorben
     * el rango que sí se usa de cara al negocio.
     *
     * Se hace en dos pasadas: primero se corren todos los códigos afectados
     * a un offset alto (fuera de cualquier rango real) para evitar choques
     * con el UNIQUE mientras se reasignan; luego se asigna el valor final.
     */
    public function up(): void
    {
        $offset = 900000;

        $activos = DB::table('clientes')
            ->whereNull('deleted_at')
            ->where('codigo', '>=', 10000)
            ->orderBy('codigo')
            ->pluck('id');

        $eliminados = DB::table('clientes')
            ->whereNotNull('deleted_at')
            ->where('codigo', '>=', 10000)
            ->orderBy('codigo')
            ->pluck('id');

        $idsEnOrden = $activos->concat($eliminados);

        if ($idsEnOrden->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($idsEnOrden, $offset) {
            foreach ($idsEnOrden as $posicion => $id) {
                DB::table('clientes')->where('id', $id)->update(['codigo' => $offset + $posicion]);
            }

            foreach ($idsEnOrden as $posicion => $id) {
                DB::table('clientes')->where('id', $id)->update(['codigo' => 10000 + $posicion]);
            }
        });
    }

    public function down(): void
    {
        // No se revierte: el orden/valor original ya no se puede reconstruir.
    }
};
