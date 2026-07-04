<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_sistema', function (Blueprint $table) {
            $table->string('apk_version')->nullable();
            $table->string('apk_url')->nullable();
            $table->text('apk_notas')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_sistema', function (Blueprint $table) {
            $table->dropColumn(['apk_version', 'apk_url', 'apk_notas']);
        });
    }
};
