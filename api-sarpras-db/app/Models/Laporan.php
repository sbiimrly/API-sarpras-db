<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Laporan extends Model
{
    use SoftDeletes;

    protected $table = 'laporan';

    protected $fillable = [
        'nama_pengusul',
        'email',
        'nomor_telepon',
        'lokasi_kerusakan',
        'deskripsi_kerusakan',
        'foto_kerusakan',
        'status_laporan'
    ];

    protected $dates = ['deleted_at'];

    // Data aktif (laporan)
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    // Data arsip
    public function scopeArchived($query)
    {
        return $query->onlyTrashed();
    }
}
