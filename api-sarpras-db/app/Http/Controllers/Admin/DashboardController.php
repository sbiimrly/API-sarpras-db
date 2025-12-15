<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getDashboardData()
    {
        // Card 1: TOTAL SEMUA LAPORAN (dari awal sampai sekarang)
        $totalLaporan = Laporan::count();

        // Card 2-5: TOTAL PER STATUS (dari awal sampai sekarang)
        $laporanMenunggu = Laporan::where('status_laporan', 'menunggu')->count();
        $laporanDiproses = Laporan::where('status_laporan', 'diproses')->count();
        $laporanSelesai = Laporan::where('status_laporan', 'terselesaikan')->count();
        $laporanDitolak = Laporan::where('status_laporan', 'ditolak')->count();

        // Data untuk chart (default 7 hari terakhir)
        $chartData = $this->getChartData(7);

        return response()->json([
            // Data untuk cards (TOTAL SEMUA)
            'total' => $totalLaporan,
            'menunggu' => $laporanMenunggu,
            'diproses' => $laporanDiproses,
            'selesai' => $laporanSelesai,
            'ditolak' => $laporanDitolak,

            // Data untuk chart (RENTANG WAKTU TERTENTU)
            'chart_labels' => $chartData['labels'],
            'chart_data' => $chartData['datasets']
        ]);
    }

    public function filter(Request $request)
    {
        $status = $request->get('status', 'all');
        $tanggal = $request->get('tanggal', '7hari');

        // Tentukan range tanggal
        switch ($tanggal) {
            case '30hari':
                $days = 30;
                break;
            case 'bulan':
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                break;
            default:
                $days = 7;
        }

        // Get chart data berdasarkan filter
        $chartData = $this->getChartData($days ?? null, $startDate ?? null, $endDate ?? null, $status);

        // Untuk filter chart, KITA TIDAK PERLU MENGEMBALIKAN SUMMARY
        // karena cards tetap menampilkan total semua laporan
        return response()->json([
            'labels' => $chartData['labels'],
            'datasets' => $chartData['datasets']
            // HAPUS: 'summary' karena cards tidak diupdate
        ]);
    }

    private function getChartData($days = 7, $startDate = null, $endDate = null, $status = 'all')
    {
        // Generate labels untuk X axis
        $labels = [];
        $datasets = [
            'menunggu' => [],
            'diproses' => [],
            'terselesaikan' => [],
            'ditolak' => []
        ];

        if ($startDate && $endDate) {
            // Filter berdasarkan bulan
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                $labels[] = $currentDate->format('d M');

                $query = Laporan::whereDate('created_at', $currentDate);
                if ($status !== 'all') {
                    $query->where('status_laporan', $status);
                }

                $datasets['menunggu'][] = $query->clone()->where('status_laporan', 'menunggu')->count();
                $datasets['diproses'][] = $query->clone()->where('status_laporan', 'diproses')->count();
                $datasets['terselesaikan'][] = $query->clone()->where('status_laporan', 'terselesaikan')->count();
                $datasets['ditolak'][] = $query->clone()->where('status_laporan', 'ditolak')->count();

                $currentDate->addDay();
            }
        } else {
            // Filter berdasarkan hari
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $labels[] = $date->format('d M');

                $query = Laporan::whereDate('created_at', $date);
                if ($status !== 'all') {
                    $query->where('status_laporan', $status);
                }

                $datasets['menunggu'][] = $query->clone()->where('status_laporan', 'menunggu')->count();
                $datasets['diproses'][] = $query->clone()->where('status_laporan', 'diproses')->count();
                $datasets['terselesaikan'][] = $query->clone()->where('status_laporan', 'terselesaikan')->count();
                $datasets['ditolak'][] = $query->clone()->where('status_laporan', 'ditolak')->count();
            }
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }
}
