<?php

namespace App\Http\Controllers\Auth;

use App\Models\Admin;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah'
            ], 401);
        }

        // Cek status admin
        if ($admin->status !== 'aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak aktif. Silakan hubungi administrator.'
            ], 403);
        }

        // Hapus token lama jika ada
        $admin->tokens()->delete();

        // Buat token baru dengan kemampuan berdasarkan role
        $abilities = $this->getAbilitiesByRole($admin->role);
        $token = $admin->createToken('admin-token')->plainTextToken;

        ActivityLog::create([
            'admin_id' => $admin->id,
            'activity' => 'Login ke Sistem',
            'type'     => 'auth',
            'details'  => [
                'ip'         => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
                'login_at'   => now()->toDateTimeString()
            ],
            'is_read'  => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $admin->id,
                    'kode' => $admin->kode,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'role' => $admin->role,
                    'status' => $admin->status,
                    'nomor_telepon' => $admin->nomor_telepon,
                ]
            ]
        ]);
    }

    protected function getAbilitiesByRole($role)
    {
        return match($role) {
            'super_admin' => ['*'], // Semua akses
            'admin' => ['view-reports', 'create-reports', 'edit-reports', 'delete-reports'],
            'viewer' => ['view-reports'],
            default => ['view-reports']
        };
    }

    public function user(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'kode' => $user->kode,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'nomor_telepon' => $user->nomor_telepon
            ]
        ]);
    }

    public function logout(Request $request)
    {
        try {
            if ($request->user() && $request->user()->currentAccessToken()) {
                // Log aktivitas logout
                ActivityLog::create([
                    'admin_id' => $request->user()->id,
                    'activity' => 'Logout dari Sistem',
                    'type'     => 'auth',
                    'details'  => [
                        'ip'         => request()->ip(),
                        'logout_at'  => now()->toDateTimeString()
                    ],
                    'is_read'  => true
                ]);
                
                $request->user()->currentAccessToken()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Logout berhasil'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout gagal: ' . $e->getMessage()
            ], 500);
        }
    }
}