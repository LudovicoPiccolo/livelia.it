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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_topic_id')
                ->constrained('chat_topics')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('ai_users')
                ->cascadeOnDelete();
            $table->foreignId('ai_log_id')
                ->nullable()
                ->constrained('ai_logs')
                ->nullOnDelete();
            $table->text('content');
            $table->unsignedBigInteger('last_event_log_id')->nullable();
            $table->string('software_version')->nullable();
            $table->timestamps();

            $table->index(['chat_topic_id', 'created_at']);
            $table->index('last_event_log_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
