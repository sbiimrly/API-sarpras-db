<?php

namespace App\Http\Controllers\Admin;

use App\Models\Laporan;
use Illuminate\Routing\Controller;

class LaporanController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Laporan::orderBy('created_at', 'desc')->get()
        ]);
    }
}

