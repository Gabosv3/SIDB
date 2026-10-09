<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lugar donde normalmente se hace la certificación notarial de los
        // contratos -- puede ser distinto al domicilio del patrono, así que
        // se guarda aparte. Se deja editable porque puede cambiar de notario.
        Schema::table('configuracion_sistema', function (Blueprint $table) {
            $table->string('notarial_distrito')->nullable();
            $table->string('notarial_municipio')->nullable();
            $table->string('notarial_departamento')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_sistema', function (Blueprint $table) {
            $table->dropColumn(['notarial_distrito', 'notarial_municipio', 'notarial_departamento']);
        });
    }
};
