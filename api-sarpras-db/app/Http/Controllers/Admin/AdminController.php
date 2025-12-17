<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controller;

class AdminController extends Controller
{
    /**
     * Menampilkan data admin
     */
    public function index(Request $request)
    {
        try {
            $query = Admin::query();

            // Filter berdasarkan status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter berdasarkan tanggal
            if ($request->has('tanggal')) {
                $now = now();
                switch ($request->tanggal) {
                    case '7hari':
                        $query->where('created_at', '>=', $now->subDays(7));
                        break;
                    case '30hari':
                        $query->where('created_at', '>=', $now->subDays(30));
                        break;
                    case 'bulan':
                        $query->whereMonth('created_at', $now->month)
                              ->whereYear('created_at', $now->year);
                        break;
                }
            }

            // Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('nomor_telepon', 'LIKE', "%{$search}%")
                      ->orWhere('kode', 'LIKE', "%{$search}%");
                });
            }

            $admins = $query->orderBy('created_at', 'desc')->get();
            $total = $admins->count();

            return response()->json([
                'success' => true,
                'message' => 'Data admin berhasil diambil',
                'data' => $admins,
                'count' => $total,
                'total' => $total
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in AdminController@index: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data admin',
                'error' => $e->getMessage(),
                'data' => [],
                'total' => 0
            ], 500);
        }
    }

    /**
     * Menambahkan admin baru
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:admin,email',
                'password' => 'required|string|min:6|confirmed',
                'nomor_telepon' => 'required|string',
                'role' => 'required|in:admin,viewer',
                'status' => 'required|in:aktif,tidak_aktif'
            ]);

            // Generate kode admin (misal: ADM001)
            $lastAdmin = Admin::orderBy('id', 'desc')->first();
            $nextNumber = $lastAdmin ? intval(substr($lastAdmin->kode, 3)) + 1 : 1;
            $kode = 'ADM' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            $admin = Admin::create([
                'kode' => $kode,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'nomor_telepon' => $request->nomor_telepon,
                'role' => $request->role,
                'status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Admin berhasil ditambahkan',
                'data' => $admin
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error in AdminController@store: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan admin'
            ], 500);
        }
    }

    /**
     * Menampilkan detail admin
     */
    public function show($id)
    {
        try {
            $admin = Admin::find($id);

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $admin
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in AdminController@show: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail admin'
            ], 500);
        }
    }

    /**
     * Update admin
     */
    public function update(Request $request, $id)
    {
        try {
            $admin = Admin::find($id);

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin tidak ditemukan'
                ], 404);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:admin,email,' . $id,
                'nomor_telepon' => 'required|string',
                'role' => 'required|in:admin,viewer',
                'status' => 'required|in:aktif,tidak_aktif'
            ]);

            // Update data
            $admin->update([
                'name' => $request->name,
                'email' => $request->email,
                'nomor_telepon' => $request->nomor_telepon,
                'role' => $request->role,
                'status' => $request->status
            ]);

            // Update password jika diisi
            if ($request->filled('password')) {
                $admin->update([
                    'password' => Hash::make($request->password)
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Admin berhasil diperbarui',
                'data' => $admin
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error in AdminController@update: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui admin'
            ], 500);
        }
    }

    /**
     * Hapus admin
     */
    public function destroy($id)
    {
        try {
            $admin = Admin::find($id);

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin tidak ditemukan'
                ], 404);
            }

            // Tidak bisa menghapus admin sendiri
            if (auth()->check() && auth()->id() == $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak bisa menghapus akun sendiri'
                ], 400);
            }

            $admin->delete();

            return response()->json([
                'success' => true,
                'message' => 'Admin berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in AdminController@destroy: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus admin'
            ], 500);
        }
    }

    /**
     * Hapus multiple admin
     */
    public function destroyMultiple(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:admin,id'
            ]);

            $ids = $request->ids;

            // Cegah menghapus diri sendiri
            if (auth()->check() && in_array(auth()->id(), $ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak bisa menghapus akun sendiri'
                ], 400);
            }

            $deletedCount = Admin::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "Berhasil menghapus {$deletedCount} admin",
                'count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in AdminController@destroyMultiple: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus admin'
            ], 500);
        }
    }

    /**
     * Update status admin
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:aktif,tidak_aktif'
            ]);

            $admin = Admin::find($id);

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin tidak ditemukan'
                ], 404);
            }

            $admin->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Status admin berhasil diperbarui',
                'data' => $admin
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in AdminController@updateStatus: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate status'
            ], 500);
        }
    }

    /**
     * Update last active time
     */
    public function updateLastActive(Request $request, $id)
    {
        try {
            $admin = Admin::find($id);

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin tidak ditemukan'
                ], 404);
            }

            $admin->update([
                'last_active_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Waktu aktif berhasil diperbarui'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in AdminController@updateLastActive: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate waktu aktif'
            ], 500);
        }
    }
}
