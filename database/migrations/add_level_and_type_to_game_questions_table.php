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
            $table->integer('level')->default(1)->after('question_number'); // Default level 1
            $table->string('question_type')->nullable()->after('level'); // e.g., 'vocal_fill', 'find_difference', 'drag_match', 'fill_blank'
            $table->json('options')->nullable()->after('instruction'); // Untuk menyimpan opsi pilihan ganda/kartu
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_questions', function (Blueprint $table) {
            $table->dropColumn(['level', 'question_type', 'options']);
        });
    }
};
