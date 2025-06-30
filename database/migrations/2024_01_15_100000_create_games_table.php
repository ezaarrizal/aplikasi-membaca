<?php

// database/migrations/2024_01_15_100000_create_games_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Games table
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('target_age'); // PlayGroup, TK, etc
            $table->string('skill_focus');
            $table->json('learning_outcomes');
            $table->string('theme');
            $table->integer('total_questions');
            $table->string('video_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Game questions table
        Schema::create('game_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->integer('question_number');
            $table->string('letter');
            $table->string('word');
            $table->string('image_path')->nullable();
            $table->string('audio_letter_path')->nullable();
            $table->string('audio_word_path')->nullable();
            $table->text('instruction');
            $table->timestamps();

            $table->index(['game_id', 'question_number']);
        });

        // Game sessions table
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->boolean('video_watched')->default(false);
            $table->timestamp('video_completed_at')->nullable();
            $table->json('questions_completed')->nullable();
            $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress');
            $table->text('teacher_notes')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'game_id']);
            $table->index('status');
        });

        // Game question attempts table
        Schema::create('game_question_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_question_id')->constrained()->onDelete('cascade');
            $table->timestamp('attempted_at');
            $table->boolean('completed')->default(false);
            $table->string('selected_answer')->nullable();
            $table->text('teacher_observation')->nullable();
            $table->integer('attempt_count')->default(1);
            $table->timestamps();

            $table->index(['game_session_id', 'game_question_id']);
        });

        // Student badges table
        Schema::create('student_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->string('badge_name');
            $table->string('badge_image_path')->nullable();
            $table->text('description');
            $table->timestamp('earned_at');
            $table->timestamps();

            $table->unique(['student_id', 'game_id']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_badges');
        Schema::dropIfExists('game_question_attempts');
        Schema::dropIfExists('game_sessions');
        Schema::dropIfExists('game_questions');
        Schema::dropIfExists('games');
    }
};
