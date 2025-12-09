<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens;
    protected $table = 'admin';
    protected $fillable = [
        'kode',
        'name',
        'email',
        'role',
        'password',
        'nomor_telepon',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
}

