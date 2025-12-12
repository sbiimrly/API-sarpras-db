<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Laporan;
use Carbon\Carbon;

class LaporanSeeder extends Seeder
{
    public function run(): void
    {
        // Hapus data lama jika ada
        Laporan::truncate();

        $laporans = [
            [
                'nama_pengusul' => 'Budi Santoso',
                'email' => 'budi@example.com',
                'nomor_telepon' => '081234567890',
                'lokasi_kerusakan' => 'Gedung A',
                'deskripsi_kerusakan' => 'AC tidak berfungsi di ruang 101',
                'foto_kerusakan' => 'laporan/ac_rusak.jpg',
                'status_laporan' => 'menunggu',
                'created_at' => Carbon::now()->subDays(2),
                'updated_at' => Carbon::now()->subDays(2),
            ],
            [
                'nama_pengusul' => 'Siti Aminah',
                'email' => 'siti@example.com',
                'nomor_telepon' => '081987654321',
                'lokasi_kerusakan' => 'Lab Komputer',
                'deskripsi_kerusakan' => 'Keyboard rusak 5 unit',
                'foto_kerusakan' => 'laporan/keyboard_rusak.jpg',
                'status_laporan' => 'diproses',
                'created_at' => Carbon::now()->subDays(1),
                'updated_at' => Carbon::now()->subDays(1),
            ],
            [
                'nama_pengusul' => 'Rudi Hermawan',
                'email' => 'rudi@example.com',
                'nomor_telepon' => '082112233445',
                'lokasi_kerusakan' => 'Ruang Baca',
                'deskripsi_kerusakan' => 'Lampu mati total',
                'foto_kerusakan' => 'laporan/lampu_mati.jpg',
                'status_laporan' => 'terselesaikan',
                'created_at' => Carbon::now()->subDays(5),
                'updated_at' => Carbon::now()->subDays(3),
            ],
            [
                'nama_pengusul' => 'Ahmad Fauzi',
                'email' => 'ahmad@example.com',
                'nomor_telepon' => '083223344556',
                'lokasi_kerusakan' => 'Toilet Lt.2',
                'deskripsi_kerusakan' => 'Keran air bocor',
                'foto_kerusakan' => 'laporan/keran_bocor.jpg',
                'status_laporan' => 'ditolak',
                'created_at' => Carbon::now()->subDays(7),
                'updated_at' => Carbon::now()->subDays(6),
            ],
        ];

        foreach ($laporans as $laporan) {
            Laporan::create($laporan);
        }

        $this->command->info('Seeder Laporan berhasil dijalankan!');
        $this->command->info('Total data: ' . count($laporans));
    }
}
