<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // DUI fotos
            $table->string('dui_foto_frente')->nullable()->after('dui');
            $table->string('dui_foto_reverso')->nullable()->after('dui_foto_frente');

            // Cónyuge
            $table->string('nombre_conyuge', 200)->nullable()->after('apellido');

            // Referencias familiares
            $table->string('ref_fam1_nombre', 150)->nullable();
            $table->string('ref_fam1_telefono', 30)->nullable();
            $table->string('ref_fam1_parentesco', 60)->nullable();

            $table->string('ref_fam2_nombre', 150)->nullable();
            $table->string('ref_fam2_telefono', 30)->nullable();
            $table->string('ref_fam2_parentesco', 60)->nullable();

            // Referencias conocidas
            $table->string('ref_con1_nombre', 150)->nullable();
            $table->string('ref_con1_telefono', 30)->nullable();
            $table->string('ref_con1_trabajo', 150)->nullable();

            $table->string('ref_con2_nombre', 150)->nullable();
            $table->string('ref_con2_telefono', 30)->nullable();
            $table->string('ref_con2_trabajo', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'dui_foto_frente', 'dui_foto_reverso', 'nombre_conyuge',
                'ref_fam1_nombre', 'ref_fam1_telefono', 'ref_fam1_parentesco',
                'ref_fam2_nombre', 'ref_fam2_telefono', 'ref_fam2_parentesco',
                'ref_con1_nombre', 'ref_con1_telefono', 'ref_con1_trabajo',
                'ref_con2_nombre', 'ref_con2_telefono', 'ref_con2_trabajo',
            ]);
        });
    }
};
