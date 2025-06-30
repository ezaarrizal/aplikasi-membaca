<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'question_number',
        'level',           // ✅ Migration kolom sudah ada
        'question_type',   // ✅ Migration kolom sudah ada
        'letter',
        'word',
        'image_path',
        'audio_letter_path',
        'audio_word_path',
        'instruction',
        'options',          // ✅ Migration kolom sudah ada
        'word_pattern'      // ⚡ NEW: Pattern untuk spelling game (e.g., "... + pu", "bi + ...")
    ];

    protected $casts = [
        'options' => 'array', // ✅ This will properly cast JSON string to array
    ];

    // Relationships
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function attempts()
    {
        return $this->hasMany(GameQuestionAttempt::class);
    }

    // Helper methods
    public function hasAudio()
    {
        return !empty($this->audio_letter_path) || !empty($this->audio_word_path);
    }

    public function hasImage()
    {
        return !empty($this->image_path);
    }

    // ⚡ NEW: Helper method for word pattern
    public function hasWordPattern()
    {
        return !empty($this->word_pattern);
    }

    // ⚡ NEW: Get display pattern for spelling game
    public function getDisplayPattern()
    {
        if ($this->hasWordPattern()) {
            return $this->word_pattern;
        }

        // Fallback: generate pattern based on question type and word
        if ($this->question_type === 'complete_word') {
            // For complete word, try to guess the pattern
            $word = strtolower($this->word);
            $letter = strtolower($this->letter);

            if (strpos($word, $letter) === 0) {
                // Letter is at the beginning: "sa + pu"
                return $letter . ' + ' . substr($word, strlen($letter));
            } else {
                // Letter is at the end: "... + sa"
                $remainingPart = str_replace($letter, '', $word);
                return '... + ' . $letter;
            }
        }

        return $this->word; // Default fallback
    }

    // ✅ EXISTING: Ensure options is always an array
    public function getOptionsAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        if (is_array($value)) {
            return $value;
        }

        return [];
    }

    // ✅ EXISTING: Ensure options is stored as JSON
    public function setOptionsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['options'] = json_encode($value);
        } else if (is_string($value)) {
            // If it's already a JSON string, store as is
            $this->attributes['options'] = $value;
        } else {
            $this->attributes['options'] = json_encode([]);
        }
    }

    // ⚡ NEW: Get correct sequence for arrange syllables
    public function getCorrectSequence()
    {
        if ($this->question_type === 'arrange_syllables') {
            return explode(',', $this->letter);
        }

        return [];
    }

    // ⚡ NEW: Check if answer is correct for spelling game
    public function checkSpellingAnswer($userAnswer, $questionType = null)
    {
        $type = $questionType ?? $this->question_type;

        switch ($type) {
            case 'complete_word':
                // Level 1: Simple syllable comparison
                return strtolower(trim($userAnswer)) === strtolower(trim($this->letter));

            case 'arrange_syllables':
                // Level 2: Check sequence
                if (is_array($userAnswer)) {
                    $correctSequence = $this->getCorrectSequence();
                    return $userAnswer === $correctSequence;
                }
                return false;

            case 'read_sentence':
                // Level 3: Always correct when user proceeds
                return true;

            default:
                return false;
        }
    }

    // ⚡ NEW: Get feedback message based on game type and correctness
    public function getFeedbackMessage($isCorrect, $questionType = null)
    {
        $type = $questionType ?? $this->question_type;

        if ($isCorrect) {
            switch ($type) {
                case 'complete_word':
                    return 'Hebat! Jawabanmu benar!';
                case 'arrange_syllables':
                    return 'Bagus! Urutan suku katanya benar!';
                case 'read_sentence':
                    return 'Bagus! Lanjut ke kalimat berikutnya!';
                default:
                    return 'Benar!';
            }
        } else {
            switch ($type) {
                case 'complete_word':
                    return 'Coba lagi ya!';
                case 'arrange_syllables':
                    return 'Urutan belum tepat, coba lagi!';
                case 'read_sentence':
                    return 'Coba baca sekali lagi!';
                default:
                    return 'Belum benar, coba lagi!';
            }
        }
    }

    // ⚡ NEW: Get appropriate audio path based on question type
    public function getAudioPath($audioType = 'word')
    {
        switch ($audioType) {
            case 'letter':
                return $this->audio_letter_path;
            case 'word':
                return $this->audio_word_path;
            default:
                return $this->audio_word_path ?? $this->audio_letter_path;
        }
    }

    // ⚡ NEW: Get level description
    public function getLevelDescription()
    {
        switch ($this->level) {
            case 1:
                return 'Level 1: Melengkapi Kata';
            case 2:
                return 'Level 2: Menyusun Suku Kata';
            case 3:
                return 'Level 3: Membaca Kalimat';
            default:
                return 'Level ' . $this->level;
        }
    }

    // ⚡ NEW: Get question type description
    public function getQuestionTypeDescription()
    {
        switch ($this->question_type) {
            case 'complete_word':
                return 'Melengkapi Kata Hilang';
            case 'arrange_syllables':
                return 'Mengurutkan Suku Kata';
            case 'read_sentence':
                return 'Membaca Kalimat Sederhana';
            case 'vocal_fill':
                return 'Mengisi Huruf Vokal';
            case 'find_difference':
                return 'Temukan Perbedaan';
            case 'drag_match':
                return 'Pasangkan Huruf';
            default:
                return ucfirst(str_replace('_', ' ', $this->question_type));
        }
    }

    // ⚡ NEW: Check if question requires teacher assistance
    public function requiresTeacherAssistance()
    {
        // Spelling game Level 3 (read_sentence) doesn't need teacher assistance
        // Detective game always needs teacher assistance
        // Vocal game needs teacher assistance for verification

        if ($this->question_type === 'read_sentence') {
            return false;
        }

        // Check if game is detective game (requires manual evaluation)
        if (in_array($this->question_type, ['find_difference', 'drag_match'])) {
            return true;
        }

        // For other types, teacher assistance is optional but recommended
        return false;
    }

    // ⚡ NEW: Get instruction text with dynamic content
    public function getDynamicInstruction()
    {
        $instruction = $this->instruction;

        // Replace placeholders with actual content
        $instruction = str_replace('{word}', $this->word, $instruction);
        $instruction = str_replace('{pattern}', $this->getDisplayPattern(), $instruction);
        $instruction = str_replace('{level}', $this->level, $instruction);

        return $instruction;
    }
}
