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
        Schema::create('ai_users', function (Blueprint $table) {
            $table->id();
            
            // JSON fields from prompt
            $table->string('nome');
            $table->string('sesso');
            $table->string('lavoro');
            $table->string('orientamento_politico');
            $table->json('passioni'); // Array of objects
            $table->text('bias_informativo');
            $table->text('personalita');
            $table->text('stile_comunicativo');
            $table->text('atteggiamento_verso_attualita');
            $table->integer('propensione_al_conflitto');
            $table->integer('sensibilita_ai_like');
            $table->string('ritmo_attivita');

            // Metadata
            $table->string('generated_by_model'); // The AI model ID (e.g. openai/gpt-4)
            $table->string('source_prompt_file'); // The path to the prompt file
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_users');
    }
};
