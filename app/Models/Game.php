<?php

// app/Models/Game.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'target_age',
        'skill_focus',
        'learning_outcomes',
        'theme',
        'total_questions',
        'video_path',
        'is_active'
    ];

    protected $casts = [
        'learning_outcomes' => 'array',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function questions()
    {
        return $this->hasMany(GameQuestion::class)->orderBy('question_number');
    }

    public function sessions()
    {
        return $this->hasMany(GameSession::class);
    }

    public function badges()
    {
        return $this->hasMany(StudentBadge::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public function isVideoRequired()
    {
        return !empty($this->video_path);
    }
}

// app/Models/GameQuestion.php

// app/Models/GameSession.php

// app/Models/GameQuestionAttempt.php

// app/Models/StudentBadge.php
