<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';

    protected $fillable = [
        'guru_id',
        'siswa_id',
        'judul',
        'isi_feedback',
        'kategori',
        'tingkat',
        'is_read_by_parent',
        'read_at',
    ];

    protected $casts = [
        'is_read_by_parent' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function siswa()
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read_by_parent', false);
    }

    public function scopeByKategori($query, $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    public function scopeByTingkat($query, $tingkat)
    {
        return $query->where('tingkat', $tingkat);
    }

    public function scopeForSiswa($query, $siswaId)
    {
        return $query->where('siswa_id', $siswaId);
    }

    public function scopeByGuru($query, $guruId)
    {
        return $query->where('guru_id', $guruId);
    }

    // Accessors
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d M Y, H:i');
    }

    public function getIsRecentAttribute()
    {
        return $this->created_at->diffInDays(now()) <= 7;
    }

    // Methods
    public function markAsRead()
    {
        $this->update([
            'is_read_by_parent' => true,
            'read_at' => now(),
        ]);
    }
}
