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
        Schema::table('game_questions', function (Blueprint $table) {
            // Add word_pattern column for spelling game support
            $table->string('word_pattern')->nullable()->after('options');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_questions', function (Blueprint $table) {
            $table->dropColumn('word_pattern');
        });
    }
};
