<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Cambiar telefono a telefono_normal
            $table->renameColumn('telefono', 'telefono_normal');

            // Agregar nuevo campo de telefonoWhatsApp
            $table->string('telefono_whatsapp', 30)->nullable()->after('telefono_normal');

            // Agregar campos de ubicación (El Salvador)
            $table->string('departamento', 100)->nullable()->after('direccion');
            $table->string('municipio', 100)->nullable()->after('departamento');
            $table->string('distrito', 100)->nullable()->after('municipio');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Revertir cambios
            $table->renameColumn('telefono_normal', 'telefono');
            $table->dropColumn(['telefono_whatsapp', 'departamento', 'municipio', 'distrito']);
        });
    }
};
