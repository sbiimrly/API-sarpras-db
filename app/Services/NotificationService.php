<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Laporan;

class NotificationService
{
    /**
     * Send notification to admin about laporan status change
     */
    public function notifyStatusChange(Laporan $laporan, string $oldStatus, string $newStatus, User $admin)
    {
        $statusText = [
            'menunggu' => 'Menunggu',
            'diproses' => 'Diproses',
            'terselesaikan' => 'Selesai',
            'ditolak' => 'Ditolak'
        ];

        $title = "Status Laporan #{$laporan->id} Berubah";
        $message = "Admin {$admin->name} mengubah status laporan dari {$statusText[$oldStatus]} menjadi {$statusText[$newStatus]}";

        return $this->createNotification(
            $admin->id, // Notifikasi untuk admin yang melakukan perubahan
            $laporan->id,
            'status_change',
            $title,
            $message,
            [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'admin_name' => $admin->name,
                'laporan_title' => $laporan->judul ?? "Laporan #{$laporan->id}"
            ]
        );
    }

    /**
     * Send notification when new laporan is created
     */
    public function notifyNewLaporan(Laporan $laporan)
    {
        $admins = User::where('role', 'admin')->get();
        
        foreach ($admins as $admin) {
            $title = "Laporan Baru Masuk";
            $message = "Laporan baru dari {$laporan->nama_pengusul} membutuhkan review";

            $this->createNotification(
                $admin->id,
                $laporan->id,
                'new_report',
                $title,
                $message,
                [
                    'nama_pengusul' => $laporan->nama_pengusul,
                    'lokasi' => $laporan->lokasi_kerusakan,
                    'created_at' => $laporan->created_at
                ]
            );
        }
    }

    /**
     * Send notification when laporan is archived
     */
    public function notifyArchived(Laporan $laporan, User $admin)
    {
        $title = "Laporan #{$laporan->id} Diarsipkan";
        $message = "Admin {$admin->name} mengarsipkan laporan ini";

        return $this->createNotification(
            $admin->id,
            $laporan->id,
            'archive',
            $title,
            $message,
            [
                'admin_name' => $admin->name,
                'archived_at' => now()
            ]
        );
    }

    /**
     * Create notification
     */
    private function createNotification($userId, $laporanId, $type, $title, $message, $data = [])
    {
        return Notification::create([
            'user_id' => $userId,
            'laporan_id' => $laporanId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data
        ]);
    }
}