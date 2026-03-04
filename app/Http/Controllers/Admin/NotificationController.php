<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Ambil notifikasi untuk admin yang sedang login
     */
    public function getNotifications(Request $request)
    {
        try {
            $adminId = session('user.id') ?? auth()->id();

            if (!$adminId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin tidak terautentikasi'
                ], 401);
            }

            $limit = $request->get('limit', 20);

            // Ambil notifikasi untuk admin ini
            $notifications = ActivityLog::where('admin_id', $adminId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            // Format response
            $formattedNotifications = $notifications->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'activity' => $notification->activity,
                    'type' => $notification->type,
                    'details' => $notification->details ?? [],
                    'is_read' => (bool) $notification->is_read,
                    'created_at' => $notification->created_at->toISOString(),
                    'human_time' => $notification->created_at->diffForHumans(),
                    'formatted_time' => $notification->created_at->format('d M Y, H:i')
                ];
            });

            $unreadCount = ActivityLog::where('admin_id', $adminId)
                ->where('is_read', false)
                ->count();

            return response()->json([
                'success' => true,
                'notifications' => $formattedNotifications,
                'unreadCount' => $unreadCount,
                'total' => $notifications->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in NotificationController@getNotifications: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil notifikasi',
                'notifications' => [],
                'unreadCount' => 0
            ], 500);
        }
    }

    /**
     * Tandai semua notifikasi sebagai dibaca
     */
    public function markAsRead(Request $request)
    {
        try {
            $adminId = session('user.id') ?? auth()->id();

            if (!$adminId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin tidak terautentikasi'
                ], 401);
            }

            $marked = ActivityLog::where('admin_id', $adminId)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'message' => "Berhasil menandai {$marked} notifikasi sebagai dibaca"
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in NotificationController@markAsRead: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menandai notifikasi'
            ], 500);
        }
    }

    /**
     * Tandai satu notifikasi spesifik sebagai dibaca
     */
    public function markSingleAsRead($id)
    {
        try {
            $adminId = session('user.id') ?? auth()->id();

            $notification = ActivityLog::where('admin_id', $adminId)
                ->where('id', $id)
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notifikasi tidak ditemukan'
                ], 404);
            }

            $notification->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Notifikasi berhasil ditandai sebagai dibaca'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in NotificationController@markSingleAsRead: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menandai notifikasi'
            ], 500);
        }
    }
}
