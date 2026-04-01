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
                    ->orWhere('lokasi_kerusakan', 'LIKE', "%{$search}%")
                    ->orWhere('nomor_telepon', 'LIKE', "%{$search}%");
                });
            }

            // Pagination - TAMBAHKAN INI
            $perPage = $request->get('per_page', 10);
            $laporan = $query->paginate($perPage);
            
            // Kembalikan dengan pagination data
            return response()->json([
                'success' => true,
                'message' => 'Data arsip berhasil diambil',
                'data' => $laporan->items(),
                'pagination' => [
                    'current_page' => $laporan->currentPage(),
                    'last_page' => $laporan->lastPage(),
                    'per_page' => $laporan->perPage(),
                    'total' => $laporan->total()
                ],
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
                'error' => $e->getMessage()
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
                'ids.*' => 'integer|exists:laporans,id'
            ]);

            $ids = $request->ids;
            $deletedCount = 0;
            $failedIds = [];

            foreach ($ids as $id) {
                $laporan = Laporan::onlyTrashed()->find($id);
                
                if ($laporan) {
                    try {
                        // Hapus file foto menggunakan Storage Laravel
                        if ($laporan->foto_kerusakan && $laporan->foto_kerusakan !== 'default.jpg') {
                            \Storage::disk('public')->delete($laporan->foto_kerusakan);
                        }

                        // Hapus permanen
                        $laporan->forceDelete();
                        $deletedCount++;
                        
                    } catch (\Exception $e) {
                        $failedIds[] = $id;
                        \Log::error("Gagal hapus permanen ID {$id}: " . $e->getMessage());
                    }
                }
            }

            $message = "Berhasil menghapus permanen {$deletedCount} laporan";
            if (count($failedIds) > 0) {
                $message .= ", " . count($failedIds) . " laporan gagal dihapus";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'count' => $deletedCount,
                'failed_ids' => $failedIds
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Error in ArsipController@destroy: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data. Silakan coba lagi.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail laporan yang diarsipkan
     */
    public function show($id)
    {
        try {
            $laporan = Laporan::onlyTrashed()->find($id);

            if (!$laporan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data arsip tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail arsip berhasil diambil',
                'data' => $laporan
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in ArsipController@show: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail arsip',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
