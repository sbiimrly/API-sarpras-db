<?php

namespace App\Http\Controllers\Admin;

use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Carbon\Carbon;

class LaporanController extends Controller
{
    /**
     * Menampilkan data laporan aktif (tidak diarsip/soft delete)
     */
    public function index(Request $request)
    {
        try {
            // Gunakan scope active (yang tidak di soft delete)
            $query = Laporan::active()->orderBy('created_at', 'desc');

            // Filter berdasarkan status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status_laporan', $request->status);
            }

            // Filter berdasarkan tanggal
            if ($request->has('tanggal') && $request->tanggal !== 'semua') {
                $today = Carbon::now();
                switch ($request->tanggal) {
                    case '7hari':
                        $query->where('created_at', '>=', $today->subDays(7));
                        break;
                    case '30hari':
                        $query->where('created_at', '>=', $today->subDays(30));
                        break;
                    case 'bulan':
                        $query->whereMonth('created_at', $today->month)
                              ->whereYear('created_at', $today->year);
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
                    'message' => 'Data laporan berhasil diambil',
                    'data' => $laporan,
                    'total' => $total,
                    'filters' => [
                        'status' => $request->status ?? 'all',
                        'tanggal' => $request->tanggal ?? null,
                        'search' => $request->search ?? null
                    ]
                ]);

            } catch (\Exception $e) {
                \Log::error('Error in LaporanController@index: ' . $e->getMessage());

                // Return JSON error juga
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengambil data laporan',
                    'error' => $e->getMessage(),
                    'data' => [],
                    'total' => 0
                ], 500);
            }
        }

    /**
     * Mengarsipkan laporan (soft delete)
     */
    public function archive(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:laporan,id'
            ]);

            $ids = $request->ids;
            $archivedCount = 0;

            foreach ($ids as $id) {
                $laporan = Laporan::find($id);
                if ($laporan && $laporan->delete()) { // Soft delete
                    $archivedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengarsipkan {$archivedCount} laporan",
                'count' => $archivedCount
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in LaporanController@archive: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengarsipkan data. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * API untuk FE: Mengambil detail laporan
     */
    public function show($id)
    {
        try {
            // Cari termasuk yang di soft delete (untuk arsip juga bisa dilihat)
            $laporan = Laporan::withTrashed()->find($id);

            if (!$laporan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laporan tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $laporan
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in LaporanController@show: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail laporan'
            ], 500);
        }
    }

    /**
     * API untuk FE: Update status laporan
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:menunggu,diproses,terselesaikan,ditolak'
            ]);

            $laporan = Laporan::find($id);

            if (!$laporan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laporan tidak ditemukan'
                ], 404);
            }

            $laporan->status_laporan = $request->status;
            $laporan->save();

            return response()->json([
                'success' => true,
                'message' => 'Status laporan berhasil diperbarui',
                'data' => $laporan
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in LaporanController@updateStatus: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate status'
            ], 500);
        }
    }

        /**
     * Hapus permanen dari halaman laporan (force delete tanpa arsip dulu)
     */
    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer'
            ]);

            $ids = $request->ids;

            // Ambil TANPA scope active
            $laporan = Laporan::withoutGlobalScopes()
                ->whereIn('id', $ids)
                ->get();

            if ($laporan->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data laporan tidak ditemukan',
                    'count' => 0
                ], 404);
            }

            // Hapus file foto
            foreach ($laporan as $item) {
                if ($item->foto_kerusakan && $item->foto_kerusakan !== 'default.jpg') {
                    $path = storage_path('app/public/' . $item->foto_kerusakan);
                    if (file_exists($path)) {
                        unlink($path);
                    }
                }
            }

            // FORCE DELETE SEKALIGUS (INI KUNCI)
            $deletedCount = Laporan::withoutGlobalScopes()
                ->whereIn('id', $ids)
                ->forceDelete();

            return response()->json([
                'success' => true,
                'message' => "Berhasil menghapus permanen {$deletedCount} laporan",
                'count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            \Log::error('Destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data'
            ], 500);
        }
    }
}
