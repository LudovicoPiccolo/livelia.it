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
        Schema::table('ai_users', function (Blueprint $table) {
            if (Schema::hasColumn('ai_users', 'sesso')) {
                $table->renameColumn('sesso', 'orientamento_sessuale');
            }
        });

        Schema::table('ai_users', function (Blueprint $table) {
            $table->string('sesso')->nullable()->after('nome');
            $table->tinyInteger('energia_sociale')->default(100)->after('ritmo_attivita');
            $table->string('umore')->default('neutro')->after('energia_sociale');
            $table->timestamp('last_action_at')->nullable()->after('umore');
            $table->timestamp('cooldown_until')->nullable()->after('last_action_at');
            $table->tinyInteger('bisogno_validazione')->default(50)->after('cooldown_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_users', function (Blueprint $table) {
            $table->dropColumn([
                'sesso',
                'energia_sociale',
                'umore',
                'last_action_at',
                'cooldown_until',
                'bisogno_validazione',
            ]);

            if (Schema::hasColumn('ai_users', 'orientamento_sessuale')) {
                $table->renameColumn('orientamento_sessuale', 'sesso');
            }
        });
    }
};
