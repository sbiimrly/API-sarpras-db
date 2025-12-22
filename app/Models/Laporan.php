<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Laporan extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'laporan';

    protected $fillable = [
        'nama_pengusul',
        'email',
        'nomor_telepon',
        'lokasi_kerusakan',
        'deskripsi_kerusakan',
        'foto_kerusakan',
        'status_laporan',
        // Additional fields for tracking
        'disetujui_oleh',
        'disetujui_pada',
        'ditolak_oleh',
        'ditolak_pada',
        'alasan_ditolak',
        'diselesaikan_oleh',
        'diselesaikan_pada',
        'bukti_penyelesaian'
    ];

    protected $dates = [
        'disetujui_pada',
        'ditolak_pada',
        'diselesaikan_pada',
        'created_at',
        'updated_at',
        'deleted_at'

    ];

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
