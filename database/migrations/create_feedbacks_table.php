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
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('siswa_id')->constrained('users')->onDelete('cascade');
            $table->string('judul');
            $table->text('isi_feedback');
            $table->enum('kategori', ['akademik', 'perilaku', 'prestasi', 'kehadiran', 'lainnya']);
            $table->enum('tingkat', ['positif', 'netral', 'perlu_perhatian'])->default('netral');
            $table->boolean('is_read_by_parent')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['siswa_id', 'created_at']);
            $table->index(['guru_id', 'created_at']);
            $table->index('is_read_by_parent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
