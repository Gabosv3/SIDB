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
        Schema::table('clientes', function (Blueprint $table) {
            $table->unsignedInteger('codigo')->nullable()->unique()->after('id');
        });

        // Backfill: clientes ya existentes reciben un código correlativo
        // arrancando en 10,000, en el orden en que se crearon.
        $siguiente = 10000;
        DB::table('clientes')->orderBy('id')->select('id')->chunkById(500, function ($clientes) use (&$siguiente) {
            foreach ($clientes as $cliente) {
                DB::table('clientes')->where('id', $cliente->id)->update(['codigo' => $siguiente]);
                $siguiente++;
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('codigo');
        });
    }
};
