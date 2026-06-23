<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            // Datos personales
            $table->string('foto')->nullable();
            $table->string('dui')->nullable()->unique();
            $table->string('nit')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('genero', ['masculino', 'femenino', 'otro'])->nullable();
            $table->enum('estado_civil', ['soltero', 'casado', 'divorciado', 'viudo', 'acompanado'])->nullable();
            $table->string('telefono_whatsapp')->nullable();
            $table->string('direccion')->nullable();
            $table->string('departamento')->nullable();
            $table->string('municipio')->nullable();

            // Datos laborales
            $table->string('codigo_empleado')->unique();
            $table->string('cargo')->nullable();
            $table->enum('tipo_empleado', ['vendedor', 'cobrador', 'administrador', 'supervisor', 'tecnico', 'otro'])->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_salida')->nullable();
            $table->decimal('salario_base', 10, 2)->nullable();
            $table->enum('tipo_contrato', ['indefinido', 'temporal', 'por_obra', 'practica'])->nullable();
            $table->string('horario_laboral')->nullable();
            $table->enum('estado_laboral', ['activo', 'suspendido', 'inactivo', 'despedido', 'renuncia'])->default('activo');
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('puede_usar_pos_movil')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
