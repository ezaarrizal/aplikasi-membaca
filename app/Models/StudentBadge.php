<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'game_id',
        'badge_name',
        'badge_image_path',
        'description',
        'earned_at'
    ];

    protected $casts = [
        'earned_at' => 'datetime'
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

    // Helper methods
    public function hasImage()
    {
        return !empty($this->badge_image_path);
    }
}
