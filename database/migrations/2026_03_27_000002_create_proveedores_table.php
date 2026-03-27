<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255)->unique();
            $table->string('codigo', 100)->unique();
            $table->string('contacto_principal', 150);
            $table->string('email', 150);
            $table->string('telefono', 20);
            $table->string('telefono_adicional', 20)->nullable();
            $table->text('direccion');
            $table->string('ciudad', 100);
            $table->string('departamento', 100);
            $table->string('pais', 100);
            $table->string('codigo_postal', 20)->nullable();
            $table->string('rfc_o_nit', 50)->nullable();
            $table->string('condiciones_pago', 100)->default('contado');
            $table->integer('dias_credito')->default(0);
            $table->decimal('descuento_comercial', 5, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->longText('notas')->nullable();
            $table->timestamps();

            $table->index('codigo');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
