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
        Schema::table('productos', function (Blueprint $table) {
            $table->string('categoria', 100)->nullable()->after('descripcion');
            $table->decimal('peso', 8, 3)->nullable()->after('categoria');
            $table->string('dimensiones', 100)->nullable()->after('peso');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['categoria', 'peso', 'dimensiones']);
        });
    }
};
