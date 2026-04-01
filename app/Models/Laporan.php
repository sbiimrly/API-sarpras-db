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
            // Jika kode_laporan sudah diisi manual (misal dari Seeder), jangan timpa lagi
            if (!$laporan->kode_laporan) {
                $year = date('Y');
                
                // Gunakan query yang lebih aman untuk mengecek nomor terakhir
                $last = self::withTrashed() // Cek juga di data yang sudah di-softdelete agar nomor tidak duplikat
                    ->where('kode_laporan', 'LIKE', "LPR-{$year}-%")
                    ->orderBy('id', 'desc')
                    ->first();

                if ($last) {
                    // Ambil 4 angka terakhir dari LPR-2026-0001
                    $lastNumber = (int) substr($last->kode_laporan, -4);
                    $number = $lastNumber + 1;
                } else {
                    $number = 1;
                }

                $laporan->kode_laporan = 'LPR-' . $year . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
            }
        });
    }
    
}
