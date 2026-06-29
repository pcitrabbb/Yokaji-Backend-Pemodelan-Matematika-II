<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Santri;

class SetoranController extends Controller
{
    /**
     * POST /setoran
     * Simpan setoran hafalan (legacy).
     */
    public function store(Request $request)
    {
        $request->validate([
            'santri_id' => 'required|integer',
            'setoran'   => 'required|string',
        ]);

        $id = DB::table('setorans')->insertGetId([
            'santri_id'  => $request->santri_id,
            'setoran'    => $request->setoran,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Setoran berhasil disimpan.', 'id' => $id], 201);
    }

    /**
     * GET /riwayat/{santri_id}
     * Riwayat setoran per santri (legacy).
     */
    public function riwayat($santri_id)
    {
        $data = DB::table('penilaians')
            ->where('santri_id', $santri_id)
            ->orderByDesc('tanggal')
            ->get();

        return response()->json(['data' => $data]);
    }

    /**
     * GET /setoran/global-stats
     * Statistik setoran bulan berjalan dari SEMUA penyimak.
     * Dipakai untuk donut chart di dashboard penyimak.
     */
    public function globalStats()
    {
        $bulan = now()->month;
        $tahun = now()->year;

        // Total santri yang sudah approved
        $totalSantri = DB::table('santri')
            ->where('status_approval', 'approved')
            ->count();

        // Santri yang sudah setor bulan ini (ada minimal 1 penilaian bulan ini, penyimak siapapun)
        $sudahSetor = DB::table('penilaians')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->whereNotNull('santri_id')
            ->distinct('santri_id')
            ->count('santri_id');

        // Pastikan tidak melebihi total
        $sudahSetor = min($sudahSetor, $totalSantri);
        $belumSetor = max(0, $totalSantri - $sudahSetor);

        return response()->json([
            'data' => [
                'sudah_setor'  => $sudahSetor,
                'belum_setor'  => $belumSetor,
                'total_santri' => $totalSantri,
                'bulan'        => $bulan,
                'tahun'        => $tahun,
            ]
        ]);
    }
}