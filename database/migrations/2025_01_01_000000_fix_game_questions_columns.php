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
        Schema::table('game_questions', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('game_questions', 'level')) {
                $table->integer('level')->default(1)->after('question_number');
            }

            if (!Schema::hasColumn('game_questions', 'question_type')) {
                $table->string('question_type')->nullable()->after('level');
            }

            if (!Schema::hasColumn('game_questions', 'options')) {
                $table->json('options')->nullable()->after('instruction');
            }

            if (!Schema::hasColumn('game_questions', 'word_pattern')) {
                $table->string('word_pattern')->nullable()->after('options');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_questions', function (Blueprint $table) {
            $columns = ['word_pattern', 'options', 'question_type', 'level'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('game_questions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
