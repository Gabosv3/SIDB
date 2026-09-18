<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preventas', function (Blueprint $table) {
            // Para cuando la persona no quiere que la registren como
            // cliente — solo se anota su nombre y teléfono para que el
            // vendedor la contacte al cerrar la venta real.
            $table->foreignId('cliente_id')->nullable()->change();
            $table->string('nombre_no_registrado', 150)->nullable()->after('cliente_id');
            $table->string('telefono_no_registrado', 30)->nullable()->after('nombre_no_registrado');
        });
    }

    public function down(): void
    {
        Schema::table('preventas', function (Blueprint $table) {
            $table->dropColumn(['nombre_no_registrado', 'telefono_no_registrado']);
            $table->foreignId('cliente_id')->nullable(false)->change();
        });
    }
};
