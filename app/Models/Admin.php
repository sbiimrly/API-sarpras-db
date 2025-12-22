<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'admin';

    protected $fillable = [
        'kode',
        'name',
        'email',
        'role',
        'password',
        'nomor_telepon',
        'status',           // TAMBAHKAN
        'last_active_at'    // TAMBAHKAN
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
    ];

    // Default values
    protected $attributes = [
        'role' => 'viewer',
        'status' => 'aktif'
    ];
}
