<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\ActivityLog;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory;
    
    protected $table = 'admin';

    protected $fillable = [
        'kode',
        'name',
        'email',
        'password',
        'role',
        'status',
        'nomor_telepon',
        'last_active_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
    ];

    // Helper methods untuk role checking
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    public function isActive(): bool
    {
        return $this->status === 'aktif';
    }

    // Scope untuk filtering
    public function scopeSuperAdmin($query)
    {
        return $query->where('role', 'super_admin');
    }

    public function scopeAdmin($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeViewer($query)
    {
        return $query->where('role', 'viewer');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'tidak_aktif');
    }

    
    //Relasi ke tabel log aktivitas.
    public function logs()
    {
        // Pastikan Anda sudah memiliki model ActivityLog di folder BE
        return $this->hasMany(ActivityLog::class, 'admin_id')->orderBy('created_at', 'desc');
    }
}