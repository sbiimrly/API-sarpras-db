<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Laporan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'laporan';

    protected $fillable = [
        'kode_laporan',
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($laporan) {

            $year = date('Y');

            $last = self::whereNotNull('kode_laporan')
                ->whereYear('created_at', $year)
                ->orderBy('id', 'desc')
                ->first();

            if ($last) {
                $lastNumber = (int) substr($last->kode_laporan, -4);
                $number = $lastNumber + 1;
            } else {
                $number = 1;
            }

            $laporan->kode_laporan =
                'LPR-' . $year . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
        });
    }
    
}
