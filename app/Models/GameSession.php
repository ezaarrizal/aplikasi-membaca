<?php

// app/Models/GameSession.php - UPDATED VERSION
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'game_id',
        'started_at',
        'completed_at',
        'video_watched',
        'video_completed_at',
        'questions_completed',
        'status',
        'teacher_notes'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'video_completed_at' => 'datetime',
        'video_watched' => 'boolean',
        'questions_completed' => 'array'
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function questionAttempts()
    {
        return $this->hasMany(GameQuestionAttempt::class);
    }

    // Helper methods
    public function getProgressPercentageAttribute()
    {
        if (!$this->questions_completed || empty($this->questions_completed)) {
            return 0;
        }

        return round((count($this->questions_completed) / $this->game->total_questions) * 100, 2);
    }

    // 👈 REMOVED: isVideoRequired method - video is now optional

    public function markVideoWatched()
    {
        $this->update([
            'video_watched' => true,
            'video_completed_at' => now()
        ]);
    }

    public function markQuestionCompleted($questionId)
    {
        $completed = $this->questions_completed ?? [];
        if (!in_array($questionId, $completed)) {
            $completed[] = $questionId;
            $this->update(['questions_completed' => $completed]);
        }

        // Check if all questions are completed
        if (count($completed) >= $this->game->total_questions) {
            $this->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
        }
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    // 👈 NEW: Check if game has video (optional)
    public function hasVideo()
    {
        return !empty($this->game->video_path);
    }

    // Scopes
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
