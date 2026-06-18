<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_devices', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('serial')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cobrador_id')->nullable()->constrained('cobradores')->nullOnDelete();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->unsignedTinyInteger('bateria')->nullable();
            $table->boolean('tiene_internet')->default(false);
            $table->string('app_version')->nullable();
            $table->text('ultimo_error')->nullable();
            $table->timestamp('ultimo_ping')->nullable();
            $table->enum('estado', ['activo', 'sin_conexion', 'bateria_baja', 'apagado'])->default('apagado');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_devices');
    }
};
