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

        $laporan = Laporan::findOrFail($id);

        $request->validate([
            'status' => 'required|in:menunggu,diproses,ditolak,terselesaikan'
        ]);

        $status = $request->status;

        $this->resetAdminFields($laporan);

        $admin = Auth::user();
        $adminKode = $admin->kode_admin ?? $admin->name;

        switch ($status) {

            case 'diproses':

                $laporan->status_laporan = 'diproses';
                $laporan->disetujui_oleh = $adminKode;
                $laporan->disetujui_pada = now();

                break;


            case 'ditolak':

                $request->validate([
                    'alasan_ditolak' => 'required|string|min:5'
                ]);

                $laporan->status_laporan = 'ditolak';
                $laporan->ditolak_oleh = $adminKode;
                $laporan->ditolak_pada = now();
                $laporan->alasan_ditolak = $request->alasan_ditolak;

                break;


            case 'terselesaikan':

                if ($laporan->status_laporan !== 'diproses') {

                    return response()->json([
                        'success' => false,
                        'message' => 'Laporan harus diproses dulu'
                    ], 400);
                }

                $request->validate([
                    'foto_selesai' => 'required|image|max:5120'
                ]);

                $file = $request->file('foto_selesai');

                $path = $file->store('foto_selesai', 'public');

                $laporan->status_laporan = 'terselesaikan';
                $laporan->diselesaikan_oleh = $adminKode;
                $laporan->diselesaikan_pada = now();
                $laporan->foto_selesai = $path;

                break;

            case 'menunggu':

                $laporan->status_laporan = 'menunggu';

                break;
        }

        $laporan->save();

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diperbarui',
            'data' => $laporan
        ]);
    }


    /**
     * Arsip laporan
     */
    public function archive(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:laporan,id'
        ]);

        $count = Laporan::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "Berhasil mengarsipkan $count laporan"
        ]);
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