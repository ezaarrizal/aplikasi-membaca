<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Game;
use App\Models\GameQuestion;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        // ==============================================
        // Game: Permainan Huruf Vokal
        // ==============================================
        $vocalGame = Game::firstOrCreate(
            ['title' => 'Permainan Huruf Vokal'],
            [
                'description' => 'Belajar mengenali dan mengartikulasikan huruf vokal A, I, U, E, O.',
                'target_age' => 'PlayGroup',
                'skill_focus' => 'Mengenal Artikulasi Huruf Vokal',
                'learning_outcomes' => json_encode([
                    'Anak dapat mengartikulasikan huruf vokal',
                    'Anak dapat mengenali huruf vokal',
                    'Anak dapat mencocokkan huruf vokal dengan kata benda'
                ]),
                'theme' => 'Taman Bermain',
                'total_questions' => 5,
                'video_path' => 'assets/games/vowels/video/intro_vowels.mp4',
                'is_active' => true
            ]
        );

        $vocalQuestionsData = [
            [ 'letter' => 'A', 'word' => 'Ayam', 'image_path' => 'assets/games/vowels/images/ayam.png', 'audio_letter_path' => 'assets/games/vowels/audio/letters/a.mp3', 'audio_word_path' => 'assets/games/vowels/audio/words/ayam.mp3', 'instruction' => 'Dengarkan suara, lalu pilih huruf awal yang tepat untuk gambar Ayam.' ],
            [ 'letter' => 'I', 'word' => 'Ikan', 'image_path' => 'assets/games/vowels/images/ikan.png', 'audio_letter_path' => 'assets/games/vowels/audio/letters/i.mp3', 'audio_word_path' => 'assets/games/vowels/audio/words/ikan.mp3', 'instruction' => 'Dengarkan suara, lalu pilih huruf awal yang tepat untuk gambar Ikan.' ],
            [ 'letter' => 'U', 'word' => 'Ular', 'image_path' => 'assets/games/vowels/images/ular.png', 'audio_letter_path' => 'assets/games/vowels/audio/letters/u.mp3', 'audio_word_path' => 'assets/games/vowels/audio/words/ular.mp3', 'instruction' => 'Dengarkan suara, lalu pilih huruf awal yang tepat untuk gambar Ular.' ],
            [ 'letter' => 'E', 'word' => 'Ember', 'image_path' => 'assets/games/vowels/images/ember.png', 'audio_letter_path' => 'assets/games/vowels/audio/letters/e.mp3', 'audio_word_path' => 'assets/games/vowels/audio/words/ember.mp3', 'instruction' => 'Dengarkan suara, lalu pilih huruf awal yang tepat untuk gambar Ember.' ],
            [ 'letter' => 'O', 'word' => 'Obat', 'image_path' => 'assets/games/vowels/images/obat.png', 'audio_letter_path' => 'assets/games/vowels/audio/letters/o.mp3', 'audio_word_path' => 'assets/games/vowels/audio/words/obat.mp3', 'instruction' => 'Dengarkan suara, lalu pilih huruf awal yang tepat untuk gambar Obat.' ],
        ];

        foreach ($vocalQuestionsData as $index => $data) {
            // ✅ FIXED: Explicitly define options as array (will be stored as JSON in DB)
            $options = ['A', 'I', 'U', 'E', 'O'];

            GameQuestion::updateOrCreate(
                ['game_id' => $vocalGame->id, 'question_number' => $index + 1],
                array_merge($data, [
                    'level' => 1,
                    'question_type' => 'vocal_fill',
                    'options' => $options, // Model will automatically cast this to JSON in DB
                ])
            );
        }

        $this->command->info('Game "Permainan Huruf Vokal" berhasil ditambahkan/diperbarui!');

        // ==============================================
        // Game: Detektif Huruf
        // ==============================================
        $detectiveGame = Game::firstOrCreate(
            ['title' => 'Detektif Huruf'],
            [
                'description' => 'Asah mata detektifmu! Temukan perbedaan huruf yang mirip, pasangkan, dan lengkapi kata.',
                'target_age' => 'TK A',
                'skill_focus' => 'Mengenali Perbedaan Huruf, Pencocokan, Melengkapi Kata',
                'learning_outcomes' => json_encode([
                    'Anak dapat membedakan huruf yang mirip (b-d, p-q, dll.)',
                    'Anak dapat mencocokkan huruf yang sama',
                    'Anak dapat melengkapi kata dengan huruf yang sesuai'
                ]),
                'theme' => 'Petualangan Detektif',
                'total_questions' => 31, // 8 + 16 + 7
                'video_path' => null,
                'is_active' => true
            ]
        );

        // Level 1: Find Difference
        $level1QuestionsData = [
            ['chars' => ['b', 'b', 'd'], 'correct' => 'd'],
            ['chars' => ['q', 'p', 'p'], 'correct' => 'q'],
            ['chars' => ['n', 'm', 'n'], 'correct' => 'm'],
            ['chars' => ['h', 'n', 'h'], 'correct' => 'n'],
            ['chars' => ['i', 'i', 'j'], 'correct' => 'j'],
            ['chars' => ['u', 'v', 'u'], 'correct' => 'v'],
            ['chars' => ['a', 'a', 'g'], 'correct' => 'g'],
            ['chars' => ['t', 'f', 't'], 'correct' => 'f'],
        ];

        foreach ($level1QuestionsData as $index => $data) {
            GameQuestion::updateOrCreate(
                ['game_id' => $detectiveGame->id, 'question_number' => ($index + 1)],
                [
                    'level' => 1,
                    'question_type' => 'find_difference',
                    'letter' => $data['correct'],
                    'word' => implode('-', $data['chars']),
                    'image_path' => null,
                    'audio_letter_path' => null,
                    'audio_word_path' => null,
                    'instruction' => 'Pilih huruf yang berbeda dari tiga kartu berikut:',
                    'options' => $data['chars'], // Model will cast array to JSON automatically
                ]
            );
        }

        // Level 2: Drag Match
        $level2Letters = ['b', 'd', 'p', 'q', 'i', 'j', 'g', 'a', 't', 'f', 'u', 'v', 'w', 'h', 'm', 'n'];

        foreach ($level2Letters as $index => $letter) {
            // ✅ FIXED: Create proper options array
            $similarLetters = $this->getRandomSimilarLetters($letter, $level2Letters, 2);
            $dragOptions = array_values(array_unique(array_merge([$letter], $similarLetters)));
            shuffle($dragOptions);

            GameQuestion::updateOrCreate(
                ['game_id' => $detectiveGame->id, 'question_number' => (8 + $index + 1)],
                [
                    'level' => 2,
                    'question_type' => 'drag_match',
                    'letter' => $letter,
                    'word' => $letter,
                    'image_path' => null,
                    'audio_letter_path' => 'assets/games/vowels/audio/letters/' . $letter . '.mp3',
                    'audio_word_path' => null,
                    'instruction' => 'Seret huruf \'' . strtoupper($letter) . '\' ke pasangannya yang benar.',
                    'options' => $dragOptions, // Model will cast array to JSON automatically
                ]
            );
        }

        // Level 3: Fill Blank
        $level3QuestionsData = [
            ['word_missing' => '__intang', 'word_full' => 'bintang', 'correct' => 'b', 'options' => ['b', 'd', 'p']],
            ['word_missing' => '__isang', 'word_full' => 'pisang', 'correct' => 'p', 'options' => ['p', 'q', 'b']],
            ['word_missing' => '__onyet', 'word_full' => 'monyet', 'correct' => 'm', 'options' => ['n', 'm', 'w']],
            ['word_missing' => '__eruk', 'word_full' => 'jeruk', 'correct' => 'j', 'options' => ['i', 'j', 'l']],
            ['word_missing' => '__nta', 'word_full' => 'unta', 'correct' => 'u', 'options' => ['u', 'v', 'w']],
            ['word_missing' => '__pel', 'word_full' => 'apel', 'correct' => 'a', 'options' => ['a', 'g', 'o']],
            ['word_missing' => '__ikus', 'word_full' => 'tikus', 'correct' => 't', 'options' => ['t', 'f', 'r']],
        ];

        foreach ($level3QuestionsData as $index => $data) {
            GameQuestion::updateOrCreate(
                ['game_id' => $detectiveGame->id, 'question_number' => (8 + 16 + $index + 1)],
                [
                    'level' => 3,
                    'question_type' => 'fill_blank',
                    'letter' => $data['correct'],
                    'word' => $data['word_full'],
                    'image_path' => 'assets/games/detektif/images/' . $data['word_full'] . '.png',
                    'audio_letter_path' => null,
                    'audio_word_path' => 'assets/games/detektif/audio/words/' . $data['word_full'] . '.mp3',
                    'instruction' => 'Lengkapi kata ini dengan huruf yang tepat:',
                    'options' => $data['options'], // Model will cast array to JSON automatically
                ]
            );
        }

        $this->command->info('Game "Detektif Huruf" berhasil ditambahkan/diperbarui!');
    }

    private function getRandomSimilarLetters($targetLetter, $allLetters, $count)
    {
        $similarMap = [
            'b' => ['d', 'p', 'q'],
            'd' => ['b', 'p', 'q'],
            'p' => ['q', 'b', 'd'],
            'q' => ['p', 'b', 'd'],
            'i' => ['j'],
            'j' => ['i'],
            'g' => ['a'],
            'a' => ['g'],
            't' => ['f'],
            'f' => ['t'],
            'u' => ['v', 'w'],
            'v' => ['u', 'w'],
            'w' => ['u', 'v'],
            'h' => ['m', 'n'],
            'm' => ['h', 'n'],
            'n' => ['h', 'm'],
        ];

        $possibleSimilar = $similarMap[strtolower($targetLetter)] ?? [];
        $possibleSimilar = array_values(array_filter($possibleSimilar, fn($char) => strtolower($char) != strtolower($targetLetter)));
        shuffle($possibleSimilar);

        return array_slice($possibleSimilar, 0, $count);
    }
}
