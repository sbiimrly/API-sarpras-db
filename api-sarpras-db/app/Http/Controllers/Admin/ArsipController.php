<?php

namespace App\Http\Controllers\Admin;

use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Carbon\Carbon;

class ArsipController extends Controller
{
    /**
     * Menampilkan data arsip (soft deleted)
     */
    public function index(Request $request)
    {
        try {
            // Gunakan onlyTrashed untuk data yang di soft delete
            $query = Laporan::onlyTrashed()->orderBy('deleted_at', 'desc');

            // Filter berdasarkan status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status_laporan', $request->status);
            }

            // Filter berdasarkan tanggal dihapus
            if ($request->has('tanggal') && $request->tanggal !== 'semua') {
                $today = Carbon::now();
                switch ($request->tanggal) {
                    case '7hari':
                        $query->where('deleted_at', '>=', $today->subDays(7));
                        break;
                    case '30hari':
                        $query->where('deleted_at', '>=', $today->subDays(30));
                        break;
                    case 'bulan':
                        $query->whereMonth('deleted_at', $today->month)
                              ->whereYear('deleted_at', $today->year);
                        break;
                }
            }

            // Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('nama_pengusul', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('lokasi_kerusakan', 'LIKE', "%{$search}%");
                });
            }

                $laporan = $query->get();
                $total = $laporan->count();

                // RETURN JSON, bukan view!
                return response()->json([
                    'success' => true,
                    'message' => 'Data arsip berhasil diambil',
                    'data' => $laporan,
                    'total' => $total,
                    'filters' => [
                        'status' => $request->status ?? 'all',
                        'tanggal' => $request->tanggal ?? null,
                        'search' => $request->search ?? null
                    ]
                ]);

            } catch (\Exception $e) {
                \Log::error('Error in ArsipController@index: ' . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengambil data arsip',
                    'error' => $e->getMessage(),
                    'data' => [],
                    'total' => 0
                ], 500);
            }
        }
    /**
     * Memulihkan data dari arsip (restore soft delete)
     */
    public function restore(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer'
            ]);

            $ids = $request->ids;
            $restoredCount = 0;

            foreach ($ids as $id) {
                // Restore dari soft delete
                $laporan = Laporan::onlyTrashed()->find($id);
                if ($laporan && $laporan->restore()) {
                    $restoredCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil memulihkan {$restoredCount} laporan dari arsip",
                'count' => $restoredCount
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in ArsipController@restore: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memulihkan data. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Menghapus permanen data arsip (force delete)
     */
    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer'
            ]);

            $ids = $request->ids;
            $deletedCount = 0;

            foreach ($ids as $id) {
                $laporan = Laporan::onlyTrashed()->find($id);
                if ($laporan) {
                    // Hapus file foto jika ada
                    if ($laporan->foto_kerusakan && $laporan->foto_kerusakan !== 'default.jpg') {
                        $path = storage_path('app/public/' . $laporan->foto_kerusakan);
                        if (file_exists($path)) {
                            unlink($path);
                        }
                    }

                    // Hapus permanen dari database
                    if ($laporan->forceDelete()) {
                        $deletedCount++;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil menghapus permanen {$deletedCount} laporan",
                'count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in ArsipController@destroy: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data. Silakan coba lagi.'
            ], 500);
        }
    }
}
