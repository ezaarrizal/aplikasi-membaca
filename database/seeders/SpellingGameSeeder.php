<?php

// database/seeders/SpellingGameSeeder.php - FIXED VERSION
namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Game;
use App\Models\GameQuestion;

class SpellingGameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🎮 Starting Spelling Game Seeder...\n";

        // Check if spelling game already exists
        $existingGame = Game::where('title', 'Belajar Mengeja')->first();

        if ($existingGame) {
            echo "⚠️ Spelling Game already exists. Updating data...\n";
            $gameId = $existingGame->id;

            // Delete existing questions to avoid duplicates
            GameQuestion::where('game_id', $gameId)->delete();
            echo "🗑️ Deleted existing questions for spelling game.\n";
        } else {
            // Create new game
            $game = Game::create([
                'title' => 'Belajar Mengeja',
                'description' => 'Belajar mengeja kata dengan menyusun suku kata dan membaca kalimat sederhana untuk anak TK B',
                'target_age' => 'TK B',
                'skill_focus' => 'Mengeja dan Membaca',
                'learning_outcomes' => [
                    'Mampu melengkapi kata yang hilang',
                    'Mampu menyusun suku kata menjadi kata utuh',
                    'Mampu membaca kalimat sederhana',
                    'Meningkatkan kemampuan fonetik dan literasi'
                ],
                'theme' => 'Mengeja',
                'total_questions' => 17, // 5 + 5 + 7
                'video_path' => 'assets/games/spelling/intro_video.mp4',
                'is_active' => true,
            ]);

            $gameId = $game->id;
            echo "✅ Created new Spelling Game with ID: $gameId\n";
        }

        // LEVEL 1: Melengkapi Kata Hilang (Complete Word)
        echo "📝 Creating Level 1 questions (Complete Word)...\n";

        $level1Questions = [
            [
                'game_id' => $gameId,
                'question_number' => 1,
                'level' => 1,
                'question_type' => 'complete_word',
                'letter' => 'sa',
                'word' => 'sapu',
                'image_path' => 'assets/games/spelling/images/sapu.png',
                'audio_letter_path' => 'assets/games/spelling/audio/syllables/sa.mp3',
                'audio_word_path' => 'assets/games/spelling/audio/words/sapu.mp3',
                'instruction' => 'Lengkapi kata di bawah ini dengan memilih suku kata yang tepat!',
                'options' => json_encode(['sa', 'si', 'so']),
                'word_pattern' => '... + pu',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'game_id' => $gameId,
                'question_number' => 2,
                'level' => 1,
                'question_type' => 'complete_word',
                'letter' => 'ru',
                'word' => 'biru',
                'image_path' => 'assets/games/spelling/images/biru.png',
                'audio_letter_path' => 'assets/games/spelling/audio/syllables/ru.mp3',
                'audio_word_path' => 'assets/games/spelling/audio/words/biru.mp3',
                'instruction' => 'Lengkapi kata di bawah ini dengan memilih suku kata yang tepat!',
                'options' => json_encode(['ra', 'ri', 'ru']),
                'word_pattern' => 'bi + ...',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'game_id' => $gameId,
                'question_number' => 3,
                'level' => 1,
                'question_type' => 'complete_word',
                'letter' => 'ro',
                'word' => 'roti',
                'image_path' => 'assets/games/spelling/images/roti.png',
                'audio_letter_path' => 'assets/games/spelling/audio/syllables/ro.mp3',
                'audio_word_path' => 'assets/games/spelling/audio/words/roti.mp3',
                'instruction' => 'Lengkapi kata di bawah ini dengan memilih suku kata yang tepat!',
                'options' => json_encode(['ro', 'ra', 'ri']),
                'word_pattern' => '... + ti',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'game_id' => $gameId,
                'question_number' => 4,
                'level' => 1,
                'question_type' => 'complete_word',
                'letter' => 'ta',
                'word' => 'pita',
                'image_path' => 'assets/games/spelling/images/pita.png',
                'audio_letter_path' => 'assets/games/spelling/audio/syllables/ta.mp3',
                'audio_word_path' => 'assets/games/spelling/audio/words/pita.mp3',
                'instruction' => 'Lengkapi kata di bawah ini dengan memilih suku kata yang tepat!',
                'options' => json_encode(['ta', 'ti', 'to']),
                'word_pattern' => 'pi + ...',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'game_id' => $gameId,
                'question_number' => 5,
                'level' => 1,
                'question_type' => 'complete_word',
                'letter' => 'ku',
                'word' => 'buku',
                'image_path' => 'assets/games/spelling/images/buku.png',
                'audio_letter_path' => 'assets/games/spelling/audio/syllables/ku.mp3',
                'audio_word_path' => 'assets/games/spelling/audio/words/buku.mp3',
                'instruction' => 'Lengkapi kata di bawah ini dengan memilih suku kata yang tepat!',
                'options' => json_encode(['ku', 'ki', 'ko']),
                'word_pattern' => 'bu + ...',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        // LEVEL 2: Mengurutkan Suku Kata (Arrange Syllables)
        echo "📝 Creating Level 2 questions (Arrange Syllables)...\n";

        $level2Questions = [
            [
                'game_id' => $gameId,
                'question_number' => 6,
                'level' => 2,
                'question_type' => 'arrange_syllables',
                'letter' => 'sa,pu,bi,ru', // Correct sequence
                'word' => 'sapu biru',
                'image_path' => 'assets/games/spelling/images/sapu_biru.png',
                'audio_letter_path' => null,
                'audio_word_path' => 'assets/games/spelling/audio/phrases/sapu_biru.mp3',
                'instruction' => 'Susun suku kata menjadi kata yang benar!',
                'options' => json_encode(['bi', 'sa', 'pu', 'ru']), // Shuffled options
                'word_pattern' => '[ ] [ ] [ ] [ ]',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'game_id' => $gameId,
                'question_number' => 7,
                'level' => 2,
                'question_type' => 'arrange_syllables',
                'letter' => 'ba,ca,bu,ku',
                'word' => 'baca buku',
                'image_path' => 'assets/games/spelling/images/baca_buku.png',
                'audio_letter_path' => null,
                'audio_word_path' => 'assets/games/spelling/audio/phrases/baca_buku.mp3',
                'instruction' => 'Susun suku kata menjadi kata yang benar!',
                'options' => json_encode(['ca', 'ba', 'ku', 'bu']),
                'word_pattern' => '[ ] [ ] [ ] [ ]',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'game_id' => $gameId,
                'question_number' => 8,
                'level' => 2,
                'question_type' => 'arrange_syllables',
                'letter' => 'ba,ju,ba,ru',
                'word' => 'baju baru',
                'image_path' => 'assets/games/spelling/images/baju_baru.png',
                'audio_letter_path' => null,
                'audio_word_path' => 'assets/games/spelling/audio/phrases/baju_baru.mp3',
                'instruction' => 'Susun suku kata menjadi kata yang benar!',
                'options' => json_encode(['ju', 'ba', 'ru', 'ba']),
                'word_pattern' => '[ ] [ ] [ ] [ ]',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'game_id' => $gameId,
                'question_number' => 9,
                'level' => 2,
                'question_type' => 'arrange_syllables',
                'letter' => 'la,ri,pa,gi',
                'word' => 'lari pagi',
                'image_path' => 'assets/games/spelling/images/lari_pagi.png',
                'audio_letter_path' => null,
                'audio_word_path' => 'assets/games/spelling/audio/phrases/lari_pagi.mp3',
                'instruction' => 'Susun suku kata menjadi kata yang benar!',
                'options' => json_encode(['pa', 'la', 'ri', 'gi']),
                'word_pattern' => '[ ] [ ] [ ] [ ]',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'game_id' => $gameId,
                'question_number' => 10,
                'level' => 2,
                'question_type' => 'arrange_syllables',
                'letter' => 'i,bu,gu,ru',
                'word' => 'ibu guru',
                'image_path' => 'assets/games/spelling/images/ibu_guru.png',
                'audio_letter_path' => null,
                'audio_word_path' => 'assets/games/spelling/audio/phrases/ibu_guru.mp3',
                'instruction' => 'Susun suku kata menjadi kata yang benar!',
                'options' => json_encode(['i', 'bu', 'ru', 'gu']),
                'word_pattern' => '[ ] [ ] [ ] [ ]',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        // LEVEL 3: Membaca Kalimat Sederhana (Read Sentences)
        echo "📝 Creating Level 3 questions (Read Sentences)...\n";

        $level3Questions = [
            [
                'game_id' => $gameId,
                'question_number' => 11,
                'level' => 3,
                'question_type' => 'read_sentence',
                'letter' => 'next', // No specific answer needed
                'word' => 'ibu beli sapu biru',
                'image_path' => null,
                'audio_letter_path' => null,
                'audio_word_path' => 'assets/games/spelling/audio/sentences/soal1.mp3',
                'instruction' => 'Bacalah kalimat di bawah ini dengan lantang!',
                'options' => json_encode([]), // No options for reading
                'word_pattern' => 'ibu beli sapu biru',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'game_id' => $gameId,
                'question_number' => 12,
                'level' => 3,
                'question_type' => 'read_sentence',
                'letter' => 'next',
                'word' => 'aku suka baca buku',
                'image_path' => null,
                'audio_letter_path' => null,
                'audio_word_path' => 'assets/games/spelling/audio/sentences/soal2.mp3',
                'instruction' => 'Bacalah kalimat di bawah ini dengan lantang!',
                'options' => json_encode([]),
                'word_pattern' => 'aku suka baca buku',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'game_id' => $gameId,
                'question_number' => 13,
                'level' => 3,
                'question_type' => 'read_sentence',
                'letter' => 'next',
                'word' => 'papa beli baju baru',
                'image_path' => null,
                'audio_letter_path' => null,
                'audio_word_path' => 'assets/games/spelling/audio/sentences/soal3.mp3',
                'instruction' => 'Bacalah kalimat di bawah ini dengan lantang!',
                'options' => json_encode([]),
                'word_pattern' => 'papa beli baju baru',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'game_id' => $gameId,
                'question_number' => 14,
                'level' => 3,
                'question_type' => 'read_sentence',
                'letter' => 'next',
                'word' => 'risa suka lagu baru',
                'image_path' => null,
                'audio_letter_path' => null,
                'audio_word_path' => 'assets/games/spelling/audio/sentences/soal4.mp3',
                'instruction' => 'Bacalah kalimat di bawah ini dengan lantang!',
                'options' => json_encode([]),
                'word_pattern' => 'risa suka lagu baru',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'game_id' => $gameId,
                'question_number' => 15,
                'level' => 3,
                'question_type' => 'read_sentence',
                'letter' => 'next',
                'word' => 'mama minum susu sapi',
                'image_path' => null,
                'audio_letter_path' => null,
                'audio_word_path' => 'assets/games/spelling/audio/sentences/soal5.mp3',
                'instruction' => 'Bacalah kalimat di bawah ini dengan lantang!',
                'options' => json_encode([]),
                'word_pattern' => 'mama minum susu sapi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'game_id' => $gameId,
                'question_number' => 16,
                'level' => 3,
                'question_type' => 'read_sentence',
                'letter' => 'next',
                'word' => 'rusa lari di hutan',
                'image_path' => null,
                'audio_letter_path' => null,
                'audio_word_path' => 'assets/games/spelling/audio/sentences/soal6.mp3',
                'instruction' => 'Bacalah kalimat di bawah ini dengan lantang!',
                'options' => json_encode([]),
                'word_pattern' => 'rusa lari di hutan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'game_id' => $gameId,
                'question_number' => 17,
                'level' => 3,
                'question_type' => 'read_sentence',
                'letter' => 'next',
                'word' => 'makan telur mata sapi',
                'image_path' => null,
                'audio_letter_path' => null,
                'audio_word_path' => 'assets/games/spelling/audio/sentences/soal7.mp3',
                'instruction' => 'Bacalah kalimat di bawah ini dengan lantang!',
                'options' => json_encode([]),
                'word_pattern' => 'makan telur mata sapi',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        // Merge all questions
        $allQuestions = array_merge($level1Questions, $level2Questions, $level3Questions);

        // Insert questions using DB::table to avoid Eloquent model caching issues
        $insertedCount = 0;
        foreach ($allQuestions as $questionData) {
            try {
                DB::table('game_questions')->insert($questionData);
                $insertedCount++;
                echo "✅ Created question {$questionData['question_number']}: {$questionData['word']}\n";

            } catch (\Exception $e) {
                echo "❌ Failed to create question {$questionData['question_number']}: {$e->getMessage()}\n";
            }
        }

        // Update total questions count
        $game = Game::find($gameId);
        $game->update(['total_questions' => $insertedCount]);

        echo "\n🎉 Spelling Game Seeding Completed!\n";
        echo "📊 Statistics:\n";
        echo "   - Game ID: $gameId\n";
        echo "   - Total Questions: $insertedCount\n";
        echo "   - Level 1 (Complete Word): 5 questions\n";
        echo "   - Level 2 (Arrange Syllables): 5 questions\n";
        echo "   - Level 3 (Read Sentences): 7 questions\n";
        echo "\n🎯 Next Steps:\n";
        echo "   1. Add images to: public/assets/games/spelling/images/\n";
        echo "   2. Add audio files to: public/assets/games/spelling/audio/\n";
        echo "   3. Add intro video to: public/assets/games/spelling/intro_video.mp4\n";
        echo "   4. Test the game in the Flutter app\n";
        echo "\n🚀 Ready to play Belajar Mengeja!\n";
    }
}
