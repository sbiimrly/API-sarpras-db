<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LaporanController extends Controller
{

    /**
     * Menampilkan laporan aktif
     */
    public function index(Request $request)
    {
        $query = Laporan::active()->orderBy('created_at', 'desc');

        // filter status
        if ($request->status && $request->status !== 'all') {
            $query->where('status_laporan', $request->status);
        }

        // filter tanggal
        if ($request->tanggal && $request->tanggal !== 'semua') {
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

        // search
        if ($request->search) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where('nama_pengusul', 'LIKE', "%$search%")
                  ->orWhere('email', 'LIKE', "%$search%")
                  ->orWhere('lokasi_kerusakan', 'LIKE', "%$search%");
            });
        }

        $laporan = $query->get();

       $formattedData = $laporan->map(function($item) {
            return [
                'id' => $item->id,
                'kode_laporan' => $item->kode_laporan ?? null,
                'nama_pengusul' => $item->nama_pengusul,
                'email' => $item->email,
                'nomor_telepon' => $item->nomor_telepon,
                'lokasi_kerusakan' => $item->lokasi_kerusakan,
                'deskripsi_kerusakan' => $item->deskripsi_kerusakan,
                'foto_kerusakan' => $item->foto_kerusakan,
                'status_laporan' => $item->status_laporan,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedData,
            'total' => $laporan->count()
        ]);
    }

    /**
     * Detail laporan
     */
    public function show($id)
    {
        $laporan = Laporan::withTrashed()->find($id);

        if (!$laporan) {

            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $laporan->id,
                'kode_laporan' => $laporan->kode_laporan ?? null,
                'nama_pengusul' => $laporan->nama_pengusul,
                'email' => $laporan->email,
                'nomor_telepon' => $laporan->nomor_telepon,
                'lokasi_kerusakan' => $laporan->lokasi_kerusakan,
                'deskripsi_kerusakan' => $laporan->deskripsi_kerusakan,
                'foto_kerusakan' => $laporan->foto_kerusakan,
                'status_laporan' => $laporan->status_laporan,
                'alasan_ditolak' => $laporan->alasan_ditolak,
                'ditolak_pada' => $laporan->ditolak_pada,
                'ditolak_oleh' => $laporan->ditolak_oleh,
                'disetujui_oleh' => $laporan->disetujui_oleh,
                'disetujui_pada' => $laporan->disetujui_pada,
                'diselesaikan_oleh' => $laporan->diselesaikan_oleh,
                'diselesaikan_pada' => $laporan->diselesaikan_pada,
                'foto_selesai' => $laporan->foto_selesai,
                'created_at' => $laporan->created_at,
                'updated_at' => $laporan->updated_at,
                'deleted_at' => $laporan->deleted_at,
            ]
        ]);
    }


    /**
     * Update status laporan
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $laporan = Laporan::findOrFail($id);
            
            // Validasi request
            $request->validate([
                'status' => 'required|in:menunggu,diproses,terselesaikan,ditolak',
                'alasan_ditolak' => 'required_if:status,ditolak|nullable|string'
            ]);
            
            // Update status
            $laporan->status_laporan = $request->status;
            $adminName = Auth::user()->name ?? 'Admin';
            
            if ($request->status === 'diproses') {
                $laporan->status_laporan = 'diproses';
                $laporan->disetujui_oleh = Auth::user()->name;
                $laporan->disetujui_pada = now();
                $laporan->save();
            } 
            elseif ($request->status === 'ditolak') {
                $laporan->alasan_ditolak = $request->alasan_ditolak;
                $laporan->ditolak_oleh = $adminName;
                $laporan->ditolak_pada = now();
            }
            elseif ($request->status === 'terselesaikan') {
                if ($request->hasFile('foto_selesai')) {
                        $path = $request->file('foto_selesai')->store('laporan/selesai', 'public');
                        $laporan->foto_selesai = $path;
                    }
                    $laporan->diselesaikan_oleh = Auth::user()->name ?? 'Admin';
                    $laporan->diselesaikan_pada = now();
            }
            
            $laporan->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diperbarui',
                'data' => $laporan
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Arsip laporan
     */
    public function archive(Request $request)
    {
         try {
            // Validasi request
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:laporan,id'
            ]);

            // Soft delete laporan yang dipilih
            $count = Laporan::whereIn('id', $request->ids)->delete();

            if ($count === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada laporan yang diarsipkan'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengarsipkan $count laporan"
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data yang dikirim tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Hapus permanen
     */
    public function destroy(Request $request)
    {

        $request->validate([
            'ids' => 'required|array'
        ]);

        $laporan = Laporan::withTrashed()->whereIn('id', $request->ids)->get();

        foreach ($laporan as $item) {

            if ($item->foto_kerusakan) {

                Storage::disk('public')->delete($item->foto_kerusakan);
            }

            if ($item->bukti_penyelesaian) {

                Storage::disk('public')->delete($item->bukti_penyelesaian);
            }

            $item->forceDelete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }

    /**
     * Memulihkan laporan dari arsip (restore)
     */
    public function restore(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer'
        ]);

        // Menggunakan onlyTrashed() karena data yang mau dipulihkan ada di sampah
        $count = Laporan::onlyTrashed()->whereIn('id', $request->ids)->restore();

        return response()->json([
            'success' => true,
            'message' => "Berhasil memulihkan $count laporan"
        ]);
    }

    /**
     * Reset field admin
     */
    private function resetAdminFields($laporan)
    {
        $laporan->disetujui_oleh = null;
        $laporan->disetujui_pada = null;

        $laporan->ditolak_oleh = null;
        $laporan->ditolak_pada = null;
        $laporan->alasan_ditolak = null;

        $laporan->diselesaikan_oleh = null;
        $laporan->diselesaikan_pada = null;
        $laporan->bukti_penyelesaian = null;
    }
}