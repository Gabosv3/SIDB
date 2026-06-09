<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->foreignId('whatsapp_account_id')
                ->nullable()
                ->after('user_id')
                ->constrained('whatsapp_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\WhatsappAccount::class);
            $table->dropColumn('whatsapp_account_id');
        });
    }
};
