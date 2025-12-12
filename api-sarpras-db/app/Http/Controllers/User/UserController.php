<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Models\Laporan;
use illuminate\Routing\Controller;

class UserController extends Controller
{
        public function store(Request $request)
    {
        $request->validate([
            'nama_pengusul' => 'required|string|max:100',
            'email' => 'required|email',
            'nomor_telepon' => 'required|string|max:15',
            'lokasi_kerusakan' => 'required|string|max:10',
            'deskripsi_kerusakan' => 'required|string|max:100',
            'foto_kerusakan' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            // Upload foto
            $fotoPath = null;
            if ($request->hasFile('foto_kerusakan')) {
                $fotoPath = $request->file('foto_kerusakan')->store('laporan', 'public');
            }

            // Simpan ke database
            $laporan = Laporan::create([
                'nama_pengusul' => $request->nama_pengusul,
                'email' => $request->email,
                'nomor_telepon' => $request->nomor_telepon,
                'lokasi_kerusakan' => $request->lokasi_kerusakan,
                'deskripsi_kerusakan' => $request->deskripsi_kerusakan,
                'foto_kerusakan' => $fotoPath,
                'status_laporan' => 'menunggu', // Default status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil dikirim',
                'data' => $laporan
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim laporan: ' . $e->getMessage()
            ], 500);
        }
    }
}
