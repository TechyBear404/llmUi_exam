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
        Schema::create('custom_instructions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // About User section
            $table->text('user_background')->nullable(); // Profession, expertise
            $table->json('user_interests')->nullable();
            $table->json('knowledge_levels')->nullable();
            $table->text('user_goals')->nullable();

            // Assistant Behavior section
            $table->text('assistant_background')->nullable(); // Profession, expertise
            $table->enum('assistant_personality', [
                'friendly',
                'professional',
                'casual',
                'formal',
                'technical',
                'educational'
            ])->default('friendly');

            $table->enum('communication_style', [
                'formal',
                'casual',
                'technical',
                'educational'
            ])->default('casual');

            $table->enum('response_style', [
                'normal',
                'concise',
                'detailed',
                'formal',
                'casual'
            ])->default('normal');

            $table->enum('response_format', [
                'paragraphs',
                'bullet_points',
                'step_by_step',
                'mixed'
            ])->default('mixed');

            // Custom Commands section
            $table->json('custom_commands')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_instructions');
    }
};
