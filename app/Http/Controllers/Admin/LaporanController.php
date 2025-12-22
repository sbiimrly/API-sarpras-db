<?php

namespace App\Http\Controllers\Admin;

use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class LaporanController extends Controller
{
    /**
     * Menampilkan data laporan aktif (tidak diarsip/soft delete)
     */
    public function index(Request $request)
    {
        try {
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
     * API untuk FE: Update status laporan dengan data admin
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $laporan = Laporan::find($id);

            if (!$laporan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laporan tidak ditemukan'
                ], 404);
            }

            // PERBAIKAN: Ambil kode admin dari session
            $adminKode = session('user.kode_admin') ?? 'ADM001';
            // Atau jika pakai auth:
            // $adminKode = auth()->user()->kode_admin ?? 'ADM001';

            // Handle different statuses
            switch ($request->status) {
                case 'diproses':
                    $laporan->status_laporan = 'diproses';
                    $laporan->disetujui_oleh = $adminKode;
                    $laporan->disetujui_pada = now();

                    // Reset other fields
                    $laporan->ditolak_oleh = null;
                    $laporan->ditolak_pada = null;
                    $laporan->alasan_ditolak = null;
                    $laporan->diselesaikan_oleh = null;
                    $laporan->diselesaikan_pada = null;
                    $laporan->bukti_penyelesaian = null;
                    break;

                case 'ditolak':
                    $request->validate([
                        'alasan_ditolak' => 'required|string|min:5'
                    ]);

                    $laporan->status_laporan = 'ditolak';
                    $laporan->ditolak_oleh = $adminKode;
                    $laporan->ditolak_pada = now();
                    $laporan->alasan_ditolak = $request->alasan_ditolak;

                    // Reset other fields
                    $laporan->disetujui_oleh = null;
                    $laporan->disetujui_pada = null;
                    $laporan->diselesaikan_oleh = null;
                    $laporan->diselesaikan_pada = null;
                    $laporan->bukti_penyelesaian = null;
                    break;

                    // Di dalam case 'terselesaikan':
                    case 'terselesaikan':
                        $request->validate([
                            'bukti_penyelesaian' => 'required|image|max:5120'
                        ]);

                        // 🔥 SIMPAN LANGSUNG KE PUBLIC
                        $folder = public_path('bukti_penyelesaian');
                        if (!file_exists($folder)) {
                            mkdir($folder, 0755, true);
                        }

                        $file = $request->file('bukti_penyelesaian');
                        $filename = 'bukti_' . time() . '_' . $laporan->id . '.' . $file->getClientOriginalExtension();
                        $file->move($folder, $filename);

                        $laporan->update([
                            'status_laporan'    => 'terselesaikan',
                            'diselesaikan_oleh' => $adminKode,
                            'diselesaikan_pada' => now(),
                            'bukti_penyelesaian'=> 'bukti_penyelesaian/' . $filename,
                        ]);
                        break;

                case 'menunggu':
                    $laporan->status_laporan = 'menunggu';
                    // Reset all admin fields
                    $laporan->disetujui_oleh = null;
                    $laporan->disetujui_pada = null;
                    $laporan->ditolak_oleh = null;
                    $laporan->ditolak_pada = null;
                    $laporan->alasan_ditolak = null;
                    $laporan->diselesaikan_oleh = null;
                    $laporan->diselesaikan_pada = null;
                    $laporan->bukti_penyelesaian = null;
                    break;
            }

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
                'message' => 'Gagal mengupdate status: ' . $e->getMessage()
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
                if ($laporan && $laporan->delete()) {
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
            $laporan = Laporan::withTrashed()->find($id);

            if (!$laporan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laporan tidak ditemukan'
                ], 404);
            }

            // Jika ada data admin ID, Anda bisa mengambil detail admin di sini
            // Contoh: join dengan tabel users atau query terpisah

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

                // Hapus bukti penyelesaian jika ada
                if ($item->bukti_penyelesaian) {
                    $path = storage_path('app/public/' . $item->bukti_penyelesaian);
                    if (file_exists($path)) {
                        unlink($path);
                    }
                }
            }

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
