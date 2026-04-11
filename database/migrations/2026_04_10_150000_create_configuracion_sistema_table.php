<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_sistema', function (Blueprint $table) {
            $table->id();

            // Identidad
            $table->string('app_name')->default('SIDB');
            $table->string('app_description')->nullable();
            $table->string('logo')->nullable();
            $table->string('logo_dark')->nullable();
            $table->string('favicon')->nullable();

            // Apariencia
            $table->string('primary_color')->nullable();

            // Contacto
            $table->string('telefono')->nullable();
            $table->string('correo_contacto')->nullable();
            $table->text('direccion')->nullable();
            $table->string('horario')->nullable();

            // Redes sociales
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->string('youtube_url')->nullable();

            // Pie de página
            $table->string('footer_texto')->nullable();
            $table->string('copyright_texto')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_sistema');
    }
};
