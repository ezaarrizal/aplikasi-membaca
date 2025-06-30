<?php

// database/migrations/2024_12_23_add_word_pattern_to_game_questions.php
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
        // ✅ DISABLED: Handled by 2025_01_01_000000_fix_game_questions_columns.php
        // No-op migration to avoid conflicts
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
