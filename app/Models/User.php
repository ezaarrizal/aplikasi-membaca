<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'password',
        'nama',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // 👈 ADD THESE ROLE CHECKING METHODS
    public function isGuru()
    {
        return $this->role === 'guru';
    }

    public function isSiswa()
    {
        return $this->role === 'siswa';
    }

    public function isOrangtua()
    {
        return $this->role === 'orangtua';
    }

    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function hasAnyRole($roles)
    {
        return in_array($this->role, $roles);
    }

    // Feedback Relationships
    public function feedbackDibuat() // untuk guru
    {
        return $this->hasMany(Feedback::class, 'guru_id');
    }

    public function feedbackDiterima() // untuk siswa
    {
        return $this->hasMany(Feedback::class, 'siswa_id');
    }

    // Parent-Child Relationships
    public function children() // untuk orangtua
    {
        return $this->belongsToMany(User::class, 'parent_child_relationships', 'parent_id', 'child_id')
                    ->withPivot('relationship_type', 'is_primary')
                    ->withTimestamps();
    }

    public function parents() // untuk siswa
    {
        return $this->belongsToMany(User::class, 'parent_child_relationships', 'child_id', 'parent_id')
                    ->withPivot('relationship_type', 'is_primary')
                    ->withTimestamps();
    }

    // Helper methods untuk relationships
    public function addChild($childId, $relationshipType = 'wali', $isPrimary = true)
    {
        return ParentChildRelationship::create([
            'parent_id' => $this->id,
            'child_id' => $childId,
            'relationship_type' => $relationshipType,
            'is_primary' => $isPrimary,
        ]);
    }

    public function removeChild($childId)
    {
        return ParentChildRelationship::where('parent_id', $this->id)
                                    ->where('child_id', $childId)
                                    ->delete();
    }

    // Get feedback untuk orangtua (dari semua anak)
    public function getFeedbackForChildren()
    {
        $childrenIds = $this->children()->pluck('id')->toArray();

        return Feedback::whereIn('siswa_id', $childrenIds)
                      ->with(['guru', 'siswa'])
                      ->orderBy('created_at', 'desc')
                      ->get();
    }

    // Get unread feedback count untuk orangtua
    public function getUnreadFeedbackCount()
    {
        $childrenIds = $this->children()->pluck('id')->toArray();

        return Feedback::whereIn('siswa_id', $childrenIds)
                      ->where('is_read_by_parent', false)
                      ->count();
    }

    // Scopes for role filtering
    public function scopeGuru($query)
    {
        return $query->where('role', 'guru');
    }

    public function scopeSiswa($query)
    {
        return $query->where('role', 'siswa');
    }

    public function scopeOrangtua($query)
    {
        return $query->where('role', 'orangtua');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
