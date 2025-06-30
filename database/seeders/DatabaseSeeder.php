<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting database seeding for Railway deployment...');

        // Seeding order is important due to foreign key constraints
        $this->call([
            UserSeeder::class,           // Creates users first
            GameSeeder::class,           // Creates games: Vokal & Detektif
            SpellingGameSeeder::class,   // Creates Spelling game (17 questions)
            // SampleDataSeeder::class,     // Creates sample game sessions, feedback, etc.
        ]);

        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->info('🎯 Ready for Flutter app testing!');
    }
}
