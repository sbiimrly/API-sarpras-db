<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function store(Request $request)
    {
        // Debug request
        \Log::info('Laporan received - Files:', $request->allFiles());
        \Log::info('Laporan received - Input:', $request->except('foto_kerusakan'));

        // Validasi
        $validated = $request->validate([
            'nama_pengusul' => 'required|string|max:100',
            'email' => 'required|email',
            'nomor_telepon' => 'required|string|max:15',
            'lokasi_kerusakan' => 'required|string|max:50',
            'deskripsi_kerusakan' => 'required|string|max:100',
            'foto_kerusakan' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Upload foto jika ada
            $fotoPath = 'default.jpg';
            if ($request->hasFile('foto_kerusakan')) {
                $file = $request->file('foto_kerusakan');
                $filename = time() . '_' . $file->getClientOriginalName();
                $fotoPath = $file->storeAs('laporan', $filename, 'public');
                \Log::info('File uploaded to: ' . $fotoPath);
            }

            // Simpan ke database
            $laporan = Laporan::create([
                'nama_pengusul' => $validated['nama_pengusul'],
                'email' => $validated['email'],
                'nomor_telepon' => $validated['nomor_telepon'],
                'lokasi_kerusakan' => $validated['lokasi_kerusakan'],
                'deskripsi_kerusakan' => $validated['deskripsi_kerusakan'],
                'foto_kerusakan' => $fotoPath,
                'status_laporan' => 'menunggu',
            ]);

            DB::commit();

            \Log::info('Laporan saved successfully', ['id' => $laporan->id]);

            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil dikirim',
                'data' => $laporan
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to save laporan', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim laporan: ' . $e->getMessage(),
                'error_details' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}
