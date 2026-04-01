<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Admin;
use Carbon\Carbon;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan data existing (opsional)
        // Admin::truncate();

        // 1. Super Admin - pemegang kendali penuh
        Admin::create([
            'kode' => 'SPR001',
            'name' => 'Super Administrator',
            'email' => 'superadmin@ft.id',
            'password' => Hash::make('SuperAdmin123'),
            'role' => 'super_admin',
            'status' => 'aktif',
            'last_active_at' => Carbon::now(),
            'nomor_telepon' => '081234567890',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 2. Admin Fakultas - dapat mengelola laporan
        $admins = [
            [
                'kode' => 'ADM001',
                'name' => 'Dr. Ahmad Wijaya',
                'email' => 'ahmad.wijaya@ft.id',
                'role' => 'admin',
                'status' => 'aktif',
                'nomor_telepon' => '081234567891',
            ],
            [
                'kode' => 'ADM002',
                'name' => 'Siti Rahmawati, S.Kom',
                'email' => 'siti.rahmawati@ft.id',
                'role' => 'admin',
                'status' => 'aktif',
                'nomor_telepon' => '081234567892',
            ],
            [
                'kode' => 'ADM003',
                'name' => 'Budi Santoso, M.T.',
                'email' => 'budi.santoso@ft.id',
                'role' => 'admin',
                'status' => 'aktif',
                'nomor_telepon' => '081234567893',
            ],
            [
                'kode' => 'ADM004',
                'name' => 'Dewi Kartika, M.Kom',
                'email' => 'dewi.kartika@ft.id',
                'role' => 'admin',
                'status' => 'tidak_aktif',
                'nomor_telepon' => '081234567894',
            ],
        ];

        foreach ($admins as $admin) {
            Admin::create([
                'kode' => $admin['kode'],
                'name' => $admin['name'],
                'email' => $admin['email'],
                'password' => Hash::make('Admin123'),
                'role' => $admin['role'],
                'status' => $admin['status'],
                'last_active_at' => Carbon::now(),
                'nomor_telepon' => $admin['nomor_telepon'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // 3. Viewer - hanya dapat melihat laporan
        $viewers = [
            [
                'kode' => 'VWR001',
                'name' => 'Rina Andriani',
                'email' => 'rina.andriani@ft.id',
                'role' => 'viewer',
                'status' => 'aktif',
                'nomor_telepon' => '081234567895',
            ],
            [
                'kode' => 'VWR002',
                'name' => 'Fajar Nugroho',
                'email' => 'fajar.nugroho@ft.id',
                'role' => 'viewer',
                'status' => 'aktif',
                'nomor_telepon' => '081234567896',
            ],
            [
                'kode' => 'VWR003',
                'name' => 'Lestari Utami',
                'email' => 'lestari.utami@ft.id',
                'role' => 'viewer',
                'status' => 'tidak_aktif',
                'nomor_telepon' => '081234567897',
            ],
        ];

        foreach ($viewers as $viewer) {
            Admin::create([
                'kode' => $viewer['kode'],
                'name' => $viewer['name'],
                'email' => $viewer['email'],
                'password' => Hash::make('Viewer123'),
                'role' => $viewer['role'],
                'status' => $viewer['status'],
                'last_active_at' => Carbon::now(),
                'nomor_telepon' => $viewer['nomor_telepon'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // 4. Tambahan dengan factory jika diperlukan (opsional)
        // Buat 10 admin tambahan dengan data random
        if (app()->environment('local')) {
            $this->command->info('Creating additional random admins for testing...');
            
            // Admin tambahan
            for ($i = 1; $i <= 5; $i++) {
                Admin::create([
                    'kode' => 'ADM' . str_pad(100 + $i, 3, '0', STR_PAD_LEFT),
                    'name' => $this->generateRandomName(),
                    'email' => $this->generateRandomEmail('admin'),
                    'password' => Hash::make('password123'),
                    'role' => 'admin',
                    'status' => $i % 2 == 0 ? 'aktif' : 'tidak_aktif',
                    'nomor_telepon' => $this->generateRandomPhone(),
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                    'updated_at' => Carbon::now()->subDays(rand(0, 10)),
                ]);
            }
            
            // Viewer tambahan
            for ($i = 1; $i <= 5; $i++) {
                Admin::create([
                    'kode' => 'VWR' . str_pad(100 + $i, 3, '0', STR_PAD_LEFT),
                    'name' => $this->generateRandomName(),
                    'email' => $this->generateRandomEmail('viewer'),
                    'password' => Hash::make('password123'),
                    'role' => 'viewer',
                    'status' => $i % 2 == 0 ? 'aktif' : 'tidak_aktif',
                    'nomor_telepon' => $this->generateRandomPhone(),
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                    'updated_at' => Carbon::now()->subDays(rand(0, 10)),
                ]);
            }
        }

        // Tampilkan ringkasan
        $this->command->info('=========================================');
        $this->command->info('Admin Seeder Completed Successfully!');
        $this->command->info('=========================================');
        $this->command->info('Total Admins: ' . Admin::count());
        $this->command->info('Super Admin: ' . Admin::where('role', 'super_admin')->count());
        $this->command->info('Admins: ' . Admin::where('role', 'admin')->count());
        $this->command->info('Viewers: ' . Admin::where('role', 'viewer')->count());
        $this->command->info('Active: ' . Admin::where('status', 'aktif')->count());
        $this->command->info('Inactive: ' . Admin::where('status', 'tidak_aktif')->count());
        $this->command->info('=========================================');
    }

    /**
     * Generate random Indonesian name
     */
    private function generateRandomName(): string
    {
        $firstNames = [
            'Ahmad', 'Budi', 'Citra', 'Dian', 'Eka', 'Fitri', 'Gilang', 'Hendra', 
            'Indah', 'Joko', 'Kartika', 'Lukman', 'Maya', 'Nugroho', 'Oktavia', 
            'Putri', 'Qodir', 'Rizki', 'Sari', 'Teguh', 'Umi', 'Vina', 'Wahyu'
        ];
        
        $lastNames = [
            'Abdullah', 'Pratama', 'Wijaya', 'Santoso', 'Hidayat', 'Nugraha', 
            'Kusuma', 'Permana', 'Setiawan', 'Gunawan', 'Wibowo', 'Saputra'
        ];
        
        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }

    /**
     * Generate random email
     */
    private function generateRandomEmail(string $role = 'admin'): string
    {
        $domains = ['ft.id', 'staff.id', 'admin.id'];
        $name = strtolower(str_replace(' ', '.', $this->generateRandomName()));
        
        return $name . '@' . $domains[array_rand($domains)];
    }

    /**
     * Generate random phone number
     */
    private function generateRandomPhone(): string
    {
        $prefixes = ['0812', '0813', '0821', '0852', '0877', '0881'];
        $number = rand(10000000, 99999999);
        
        return $prefixes[array_rand($prefixes)] . $number;
    }
}