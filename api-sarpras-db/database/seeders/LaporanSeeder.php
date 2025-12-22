<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Laporan;
use Carbon\Carbon;

class LaporanSeeder extends Seeder
{
    public function run(): void
    {
        Laporan::truncate();

        $laporans = [
            // Data dalam 7 hari terakhir (untuk chart)
            [
                'nama_pengusul' => 'Budi Santoso',
                'email' => 'budi@example.com',
                'nomor_telepon' => '081234567890',
                'lokasi_kerusakan' => 'Gedung A',
                'deskripsi_kerusakan' => 'AC tidak berfungsi di ruang 101',
                'foto_kerusakan' => 'default.jpg',
                'status_laporan' => 'menunggu',
                'created_at' => Carbon::now()->subDays(2), // 2 hari lalu
                'updated_at' => Carbon::now()->subDays(2),
            ],
            [
                'nama_pengusul' => 'Siti Aminah',
                'email' => 'siti@example.com',
                'nomor_telepon' => '081987654321',
                'lokasi_kerusakan' => 'Lab Komputer',
                'deskripsi_kerusakan' => 'Keyboard rusak 5 unit',
                'foto_kerusakan' => 'default.jpg',
                'status_laporan' => 'diproses',
                'created_at' => Carbon::now()->subDays(1), // 1 hari lalu
                'updated_at' => Carbon::now()->subDays(1),
            ],
            [
                'nama_pengusul' => 'Rudi Hermawan',
                'email' => 'rudi@example.com',
                'nomor_telepon' => '082112233445',
                'lokasi_kerusakan' => 'Ruang Baca',
                'deskripsi_kerusakan' => 'Lampu mati total',
                'foto_kerusakan' => 'default.jpg',
                'status_laporan' => 'terselesaikan',
                'created_at' => Carbon::now()->subDays(3), // 3 hari lalu
                'updated_at' => Carbon::now()->subDays(3),
            ],
            [
                'nama_pengusul' => 'Ahmad Fauzi',
                'email' => 'ahmad@example.com',
                'nomor_telepon' => '083223344556',
                'lokasi_kerusakan' => 'Toilet Lt.2',
                'deskripsi_kerusakan' => 'Keran air bocor',
                'foto_kerusakan' => 'default.jpg',
                'status_laporan' => 'ditolak',
                'created_at' => Carbon::now()->subDays(4), // 4 hari lalu
                'updated_at' => Carbon::now()->subDays(4),
            ],
            [
                'nama_pengusul' => 'Joko Widodo',
                'email' => 'joko@example.com',
                'nomor_telepon' => '084334455667',
                'lokasi_kerusakan' => 'Kantin',
                'deskripsi_kerusakan' => 'Kursi patah 3 buah',
                'foto_kerusakan' => 'default.jpg',
                'status_laporan' => 'terselesaikan',
                'created_at' => Carbon::now()->subDays(5), // 5 hari lalu
                'updated_at' => Carbon::now()->subDays(5),
            ],
            [
                'nama_pengusul' => 'Sri Mulyani',
                'email' => 'sri@example.com',
                'nomor_telepon' => '085445566778',
                'lokasi_kerusakan' => 'Perpustakaan',
                'deskripsi_kerusakan' => 'Rak buku miring',
                'foto_kerusakan' => 'default.jpg',
                'status_laporan' => 'menunggu',
                'created_at' => Carbon::now()->subDays(6), // 6 hari lalu
                'updated_at' => Carbon::now()->subDays(6),
            ],
            [
                'nama_pengusul' => 'Bambang Pamungkas',
                'email' => 'bambang@example.com',
                'nomor_telepon' => '086556677889',
                'lokasi_kerusakan' => 'Lapangan Basket',
                'deskripsi_kerusakan' => 'Ring basket longgar',
                'foto_kerusakan' => 'default.jpg',
                'status_laporan' => 'diproses',
                'created_at' => Carbon::now()->subDays(0), // Hari ini
                'updated_at' => Carbon::now()->subDays(0),
            ],
        ];

        foreach ($laporans as $laporan) {
            Laporan::create($laporan);
        }

        $this->command->info('Seeder Laporan berhasil dijalankan!');
        $this->command->info('Total data: ' . count($laporans));

        // Tampilkan summary
        $this->command->info('Summary:');
        $this->command->info('- Menunggu: ' . Laporan::where('status_laporan', 'menunggu')->count());
        $this->command->info('- Diproses: ' . Laporan::where('status_laporan', 'diproses')->count());
        $this->command->info('- Terselesaikan: ' . Laporan::where('status_laporan', 'terselesaikan')->count());
        $this->command->info('- Ditolak: ' . Laporan::where('status_laporan', 'ditolak')->count());
    }
}
