<?php

namespace App\Http\Controllers\User;  // PERHATIKAN: User bukan Controller

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class UserController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Debug log
            \Log::info('=== Laporan Submission Start ===');
            \Log::info('Request Data:', $request->except('foto_kerusakan'));

            // Check rate limit
            $key = 'laporan:' . $request->ip() . ':' . $request->input('email');
            if (RateLimiter::tooManyAttempts($key, 1)) {
                $seconds = RateLimiter::availableIn($key);

                return response()->json([
                    'success' => false,
                    'message' => 'Anda baru saja mengirim laporan. Tunggu 10 menit untuk mengirim laporan berikutnya.',
                    'wait_time' => $seconds,
                    'rate_limited' => true
                ], 429);
            }

            // Rate limiter hit
            RateLimiter::hit($key, 600);

            // Validasi
            $validated = $request->validate([
                'nama_pengusul' => 'required|string|max:100',
                'email' => 'required|email|max:100',
                'nomor_telepon' => 'required|string|max:15',
                'lokasi_kerusakan' => 'required|string|max:50',
                'deskripsi_kerusakan' => 'required|string|max:100',
                'foto_kerusakan' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            DB::beginTransaction();

            // Upload foto jika ada
            $fotoPath = null;
            if ($request->hasFile('foto_kerusakan')) {
                $file = $request->file('foto_kerusakan');
                $filename = time() . '_' . $file->getClientOriginalName();
                $fotoPath = $file->store('laporan', 'public');
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
                'message' => 'Laporan berhasil dikirim!',
                'laporan_id' => $laporan->id,
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            // Clear rate limit jika gagal
            if (isset($key)) {
                RateLimiter::clear($key);
            }

            \Log::error('Failed to save laporan:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim laporan. Silakan coba lagi.'
            ], 500);
        }
    }
}
