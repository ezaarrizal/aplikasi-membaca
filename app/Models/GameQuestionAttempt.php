<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameQuestionAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'game_question_id',
        'attempted_at',
        'completed',
        'selected_answer',
        'teacher_observation',
        'attempt_count'
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'completed' => 'boolean'
    ];

    // Relationships
    public function session()
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function question()
    {
        return $this->belongsTo(GameQuestion::class, 'game_question_id');
    }

    // Helper methods
    public function isCorrect()
    {
        return $this->completed;
    }
}
