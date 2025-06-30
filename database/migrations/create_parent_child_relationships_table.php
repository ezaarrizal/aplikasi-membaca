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
        Schema::create('parent_child_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('child_id')->constrained('users')->onDelete('cascade');
            $table->enum('relationship_type', ['ayah', 'ibu', 'wali'])->default('wali');
            $table->boolean('is_primary')->default(true); // Primary contact
            $table->timestamps();

            // Prevent duplicate relationships
            $table->unique(['parent_id', 'child_id']);

            // Indexes
            $table->index('parent_id');
            $table->index('child_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_child_relationships');
    }
};
