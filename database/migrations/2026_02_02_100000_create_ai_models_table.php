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
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('model_id')->unique(); // The 'id' from OpenRouter (e.g. openai/gpt-4)
            $table->string('canonical_slug')->nullable();
            $table->string('name')->nullable();
            $table->json('pricing')->nullable();
            $table->json('architecture')->nullable();
            
            $table->boolean('is_free')->default(false);
            $table->boolean('was_free')->default(false);
            
            // Modalities
            $table->boolean('is_text')->default(false);
            $table->boolean('is_audio')->default(false);
            $table->boolean('is_image')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
