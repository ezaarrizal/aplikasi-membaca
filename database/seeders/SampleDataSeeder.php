<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\GameQuestionAttempt;
use App\Models\StudentBadge;
use App\Models\Feedback;
use Carbon\Carbon;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📊 Creating sample data for testing...');

        // Get users and games
        $guru = User::where('role', 'guru')->first();
        $siswa1 = User::where('username', 'siswa001')->first();
        $siswa2 = User::where('username', 'siswa002')->first();
        $siswa3 = User::where('username', 'siswa003')->first();

        $vocalGame = Game::where('title', 'Permainan Huruf Vokal')->first();
        $detectiveGame = Game::where('title', 'Detektif Huruf')->first();

        if (!$guru || !$siswa1 || !$vocalGame) {
            $this->command->error('Required users or games not found. Run UserSeeder and GameSeeder first.');
            return;
        }

        // Create completed game session for siswa1 (successful)
        $completedSession = GameSession::create([
            'student_id' => $siswa1->id,
            'game_id' => $vocalGame->id,
            'started_at' => Carbon::now()->subDays(2),
            'completed_at' => Carbon::now()->subDays(2)->addMinutes(15),
            'video_watched' => true,
            'video_completed_at' => Carbon::now()->subDays(2)->addMinutes(2),
            'questions_completed' => json_encode([1, 2, 3, 4, 5]),
            'status' => 'completed',
            'teacher_notes' => 'Andi sangat antusias dan mampu menyelesaikan semua soal dengan baik.',
        ]);

        // Create question attempts for completed session
        $questions = $vocalGame->gameQuestions()->take(5)->get();
        foreach ($questions as $index => $question) {
            GameQuestionAttempt::create([
                'game_session_id' => $completedSession->id,
                'game_question_id' => $question->id,
                'attempted_at' => Carbon::now()->subDays(2)->addMinutes(3 + $index * 2),
                'completed' => true,
                'selected_answer' => $question->letter, // Correct answer
                'teacher_observation' => $index < 3
                    ? 'Jawaban benar, pelafalan jelas.'
                    : 'Sempat ragu tapi akhirnya benar.',
                'attempt_count' => $index >= 3 ? 2 : 1, // Last 2 questions needed retry
            ]);
        }

        // Create badge for successful completion
        StudentBadge::create([
            'student_id' => $siswa1->id,
            'game_id' => $vocalGame->id,
            'badge_name' => 'Ahli Huruf Vokal',
            'badge_image_path' => 'assets/badges/vocal_master.png',
            'description' => 'Berhasil menguasai semua huruf vokal A, I, U, E, O',
            'earned_at' => $completedSession->completed_at,
        ]);

        // Create in-progress session for siswa2
        $inProgressSession = GameSession::create([
            'student_id' => $siswa2->id,
            'game_id' => $detectiveGame->id,
            'started_at' => Carbon::now()->subHour(),
            'completed_at' => null,
            'video_watched' => true,
            'video_completed_at' => Carbon::now()->subMinutes(50),
            'questions_completed' => json_encode([1, 2, 3]),
            'status' => 'in_progress',
            'teacher_notes' => null,
        ]);

        // Create some attempts for in-progress session
        $detectiveQuestions = $detectiveGame->gameQuestions()->take(3)->get();
        foreach ($detectiveQuestions as $index => $question) {
            GameQuestionAttempt::create([
                'game_session_id' => $inProgressSession->id,
                'game_question_id' => $question->id,
                'attempted_at' => Carbon::now()->subMinutes(45 - $index * 10),
                'completed' => $index < 2, // First 2 completed, 3rd not yet
                'selected_answer' => $index < 2 ? $question->letter : null,
                'teacher_observation' => $index < 2 ? 'Bagus, cepat mengenali perbedaan.' : null,
                'attempt_count' => 1,
            ]);
        }

        // Create abandoned session for siswa3
        GameSession::create([
            'student_id' => $siswa3->id,
            'game_id' => $vocalGame->id,
            'started_at' => Carbon::now()->subDays(1),
            'completed_at' => null,
            'video_watched' => false,
            'video_completed_at' => null,
            'questions_completed' => json_encode([]),
            'status' => 'abandoned',
            'teacher_notes' => 'Budi terlihat tidak fokus, perlu pendampingan lebih.',
        ]);

        // Create feedback from guru to siswa1 (positive)
        Feedback::create([
            'guru_id' => $guru->id,
            'siswa_id' => $siswa1->id,
            'judul' => 'Prestasi Luar Biasa dalam Huruf Vokal!',
            'isi_feedback' => 'Andi menunjukkan kemajuan yang sangat baik dalam mengenal huruf vokal. Pelafalan sudah jelas dan mampu mengidentifikasi huruf dengan tepat. Sangat antusias dalam mengikuti pembelajaran.',
            'kategori' => 'prestasi',
            'tingkat' => 'positif',
            'is_read_by_parent' => true,
            'read_at' => Carbon::now()->subHours(5),
        ]);

        // Create feedback from guru to siswa2 (neutral)
        Feedback::create([
            'guru_id' => $guru->id,
            'siswa_id' => $siswa2->id,
            'judul' => 'Progress Pembelajaran Sari',
            'isi_feedback' => 'Sari sudah mulai menunjukkan kemajuan dalam mengenali perbedaan huruf. Masih perlu latihan lebih untuk huruf-huruf yang mirip seperti b dan d. Tetap semangat belajar!',
            'kategori' => 'akademik',
            'tingkat' => 'netral',
            'is_read_by_parent' => false,
            'read_at' => null,
        ]);

        // Create feedback from guru to siswa3 (needs attention)
        Feedback::create([
            'guru_id' => $guru->id,
            'siswa_id' => $siswa3->id,
            'judul' => 'Perlu Perhatian Khusus',
            'isi_feedback' => 'Budi tampak kesulitan fokus saat pembelajaran. Disarankan untuk memberikan pendampingan lebih intensif di rumah dan membatasi waktu bermain gadget sebelum belajar.',
            'kategori' => 'perilaku',
            'tingkat' => 'perlu_perhatian',
            'is_read_by_parent' => false,
            'read_at' => null,
        ]);

        $this->command->info('✅ Sample data created successfully:');
        $this->command->info('   📱 1 Completed game session with badge');
        $this->command->info('   ⏳ 1 In-progress game session');
        $this->command->info('   ❌ 1 Abandoned game session');
        $this->command->info('   💬 3 Teacher feedback messages');
        $this->command->info('   🏆 1 Student badge earned');
        $this->command->info('   📊 Ready for testing all app features!');
    }
}
