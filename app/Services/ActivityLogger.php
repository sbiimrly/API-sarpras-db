<?php
namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Admin;

class ActivityLogger
{
    public static function log($adminId, $activity, $type, $details = null, $laporanId = null)
    {
        // Convert details to JSON if it's an array
        $detailsJson = is_array($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : $details;

        return ActivityLog::create([
            'admin_id' => $adminId,
            'activity' => $activity,
            'type' => $type,
            'details' => $detailsJson,
            'laporan_id' => $laporanId,
            'is_read' => false,
        ]);
    }

    public static function logLaporanActivity($adminId, $laporan, $action)
    {
        $map = [
            'create'  => 'Laporan Baru Masuk',
            'update'  => 'Status Laporan Diubah',
            'archive' => 'Laporan Diarsipkan',
            'restore' => 'Laporan Dipulihkan',
        ];

        return self::log(
            $adminId,
            $map[$action] ?? 'Aktivitas Laporan',
            'laporan',
            [
                'laporan_id'   => $laporan->id,
                'nama_pengusul'=> $laporan->nama_pengusul,
                'status'       => $laporan->status_laporan,
                'waktu'        => now()->toDateTimeString(),
            ],
            $laporan->id
        );
    }

    /**
     * Log untuk laporan baru (tanpa admin karena dari user)
     */
    public static function logNewLaporan($laporan)
    {
        return self::log(
            null, // Tidak ada admin untuk laporan baru
            'Laporan Baru Masuk',
            'laporan_create',
            [
                'laporan_id' => $laporan->id,
                'nama_pengusul' => $laporan->nama_pengusul,
                'lokasi_kerusakan' => $laporan->lokasi_kerusakan,
                'waktu' => now()->toDateTimeString()
            ],
            $laporan->id
        );
    }
}
