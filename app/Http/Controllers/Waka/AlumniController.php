<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlumniController extends Controller
{
    /**
     * Tampilkan data master alumni & terapkan retensi otomatis (auto-purge data expired)
     */
    public function index(Request $request): Response
    {
        $currentYear = (int) date('Y');
        $retentionThresholdYear = $currentYear - 1;

        // Kebijakan retensi otomatis: Jika data alumni telah melewati batas retensi 1 tahun,
        // data tersebut otomatis dibersihkan langsung oleh sistem saat dimuat.
        Alumni::where('tahun_lulus', '<', $retentionThresholdYear)->delete();

        $search = $request->input('search');
        $tahunLulus = $request->input('tahun_lulus');

        $query = Alumni::orderByDesc('tahun_lulus')->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('kelas_terakhir', 'like', "%{$search}%");
            });
        }

        if ($tahunLulus && $tahunLulus !== 'all') {
            $query->where('tahun_lulus', $tahunLulus);
        }

        $alumnis = $query->paginate(20)->withQueryString();

        $totalActive = Alumni::count();
        $tahunList = Alumni::distinct()->orderByDesc('tahun_lulus')->pluck('tahun_lulus')->filter();

        return Inertia::render('Waka/Alumni/Index', [
            'alumnis' => $alumnis,
            'filters' => [
                'search' => $search,
                'tahun_lulus' => $tahunLulus,
            ],
            'stats' => [
                'total' => $totalActive,
                'active' => $totalActive,
                'retention_threshold' => $retentionThresholdYear,
                'current_year' => $currentYear,
            ],
            'tahunList' => $tahunList,
        ]);
    }
}
