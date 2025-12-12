<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getDashboardData()
    {
        // PERBAIKAN: Gunakan 'status_laporan' bukan 'status'
        $totalLaporan = Laporan::count();
        $laporanMenunggu = Laporan::where('status_laporan', 'menunggu')->count();
        $laporanDiproses = Laporan::where('status_laporan', 'diproses')->count();
        $laporanSelesai = Laporan::where('status_laporan', 'terselesaikan')->count();
        $laporanDitolak = Laporan::where('status_laporan', 'ditolak')->count();

        // Data untuk chart (7 hari terakhir)
        $chartData = $this->getChartData(7);

        return response()->json([
            'total' => $totalLaporan,
            'menunggu' => $laporanMenunggu,
            'diproses' => $laporanDiproses,
            'selesai' => $laporanSelesai,
            'ditolak' => $laporanDitolak,
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

        // Get summary berdasarkan filter
        $query = Laporan::query();

        if ($status !== 'all') {
            $query->where('status_laporan', $status); // PERBAIKAN: 'status_laporan'
        }

        if (isset($startDate) && isset($endDate)) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif (isset($days)) {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $summary = [
            'total' => $query->count(),
            'menunggu' => $query->clone()->where('status_laporan', 'menunggu')->count(), // PERBAIKAN
            'diproses' => $query->clone()->where('status_laporan', 'diproses')->count(), // PERBAIKAN
            'selesai' => $query->clone()->where('status_laporan', 'terselesaikan')->count(), // PERBAIKAN
        ];

        return response()->json([
            'labels' => $chartData['labels'],
            'datasets' => $chartData['datasets'],
            'summary' => $summary
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
                    $query->where('status_laporan', $status); // PERBAIKAN
                }

                $datasets['menunggu'][] = $query->clone()->where('status_laporan', 'menunggu')->count(); // PERBAIKAN
                $datasets['diproses'][] = $query->clone()->where('status_laporan', 'diproses')->count(); // PERBAIKAN
                $datasets['terselesaikan'][] = $query->clone()->where('status_laporan', 'terselesaikan')->count(); // PERBAIKAN
                $datasets['ditolak'][] = $query->clone()->where('status_laporan', 'ditolak')->count(); // PERBAIKAN

                $currentDate->addDay();
            }
        } else {
            // Filter berdasarkan hari
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $labels[] = $date->format('d M');

                $query = Laporan::whereDate('created_at', $date);
                if ($status !== 'all') {
                    $query->where('status_laporan', $status); // PERBAIKAN
                }

                $datasets['menunggu'][] = $query->clone()->where('status_laporan', 'menunggu')->count(); // PERBAIKAN
                $datasets['diproses'][] = $query->clone()->where('status_laporan', 'diproses')->count(); // PERBAIKAN
                $datasets['terselesaikan'][] = $query->clone()->where('status_laporan', 'terselesaikan')->count(); // PERBAIKAN
                $datasets['ditolak'][] = $query->clone()->where('status_laporan', 'ditolak')->count(); // PERBAIKAN
            }
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }
}
