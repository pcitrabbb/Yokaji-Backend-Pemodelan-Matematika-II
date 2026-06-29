<?php

namespace App\Http\Controllers\Api\Penyimak;

use App\Http\Controllers\Controller;
use App\Models\Penilaian;
use App\Models\Penyimak;
use App\Models\Santri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PenilaianController extends Controller
{
    private function getPenyimak()
    {
        return Penyimak::where('user_id', Auth::id())->firstOrFail();
    }

    public function index(Request $request)
    {
        $penyimak = $this->getPenyimak();
        $limit    = $request->query('limit', 10);

        $query = Penilaian::with('santri.user')
            ->where('penyimak_id', $penyimak->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('created_at');

        // ── Support filter dari & sampai ──
        if ($request->query('dari')) {
            $query->where('tanggal', '>=', $request->query('dari'));
        }
        if ($request->query('sampai')) {
            $query->where('tanggal', '<=', $request->query('sampai'));
        }

        $data = $query->limit($limit)->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'santri_id'   => $p->santri_id,
                'santri_nama' => $p->santri->user->name ?? '—',
                'setoran'     => $p->setoran,
                'kesalahan'   => $p->kesalahan,
                'nilai'       => $p->nilai,
                'status'      => $p->status,
                'catatan'     => $p->catatan,
                'tanggal'     => $p->tanggal,
            ]);

        return response()->json(['data' => $data]);
    }

    public function count(Request $request)
    {
        $penyimak = $this->getPenyimak();

        $query = Penilaian::where('penyimak_id', $penyimak->id);

        if ($request->query('dari')) {
            $query->where('tanggal', '>=', $request->query('dari'));
        }
        if ($request->query('sampai')) {
            $query->where('tanggal', '<=', $request->query('sampai'));
        }

        return response()->json(['total' => $query->count()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'santri_id' => 'required',
            'setoran'   => 'required|string',
            'kesalahan' => 'nullable|integer|min:0',
            'catatan'   => 'nullable|string',
        ]);

        $penyimak  = $this->getPenyimak();
        $kesalahan = (int) $request->kesalahan;
        $nilai     = $request->nilai !== null ? (int) $request->nilai : max(0, 100 - $kesalahan * 2);
        $status    = match(true) {
            $nilai >= 90 => 'Sangat Bagus',
            $nilai >= 80 => 'Bagus',
            $nilai >= 70 => 'Baik',
            $nilai >= 60 => 'Cukup',
            default      => 'Kurang',
        };

        $penilaian = Penilaian::create([
            'penyimak_id' => $penyimak->id,
            'santri_id'   => $request->santri_id,
            'setoran'     => $request->setoran,
            'kesalahan'   => $kesalahan,
            'nilai'       => $nilai,
            'status'      => $request->status ?? $status,
            'catatan'     => $request->catatan,
            'tanggal'     => Carbon::today(),
            'total_ayat'  => $request->total_ayat ?? 0,
        ]);

        return response()->json($penilaian, 201);
    }

    public function stats()
    {
        $penyimak = $this->getPenyimak();
        $bulanIni = Carbon::now();

        $totalPenilaian = Penilaian::where('penyimak_id', $penyimak->id)->count();
        $rataRata       = Penilaian::where('penyimak_id', $penyimak->id)->avg('nilai');
        $santriAktif    = Penilaian::where('penyimak_id', $penyimak->id)
                            ->distinct('santri_id')->count('santri_id');

        $totalBulan = Penilaian::where('penyimak_id', $penyimak->id)
                        ->whereYear('tanggal', $bulanIni->year)
                        ->whereMonth('tanggal', $bulanIni->month)
                        ->count();

        $selesai = Penilaian::where('penyimak_id', $penyimak->id)
                    ->whereYear('tanggal', $bulanIni->year)
                    ->whereMonth('tanggal', $bulanIni->month)
                    ->whereIn('status', ['Sangat Bagus', 'Bagus', 'Baik'])
                    ->count();

        $proses = Penilaian::where('penyimak_id', $penyimak->id)
                    ->whereYear('tanggal', $bulanIni->year)
                    ->whereMonth('tanggal', $bulanIni->month)
                    ->where('status', 'Cukup')
                    ->count();

        $belum = Penilaian::where('penyimak_id', $penyimak->id)
                    ->whereYear('tanggal', $bulanIni->year)
                    ->whereMonth('tanggal', $bulanIni->month)
                    ->where('status', 'Kurang')
                    ->count();

        // ── Donut: sudah/belum setor bulan ini (semua penyimak, semua santri) ──
        // "Sudah setor" = santri yang punya minimal 1 penilaian bulan ini
        $totalSantri = Santri::count();

        $sudahSetorIds = Penilaian::whereYear('tanggal', $bulanIni->year)
                            ->whereMonth('tanggal', $bulanIni->month)
                            ->distinct()
                            ->pluck('santri_id');

        $sudahSetor = $sudahSetorIds->count();
        $belumSetor = max(0, $totalSantri - $sudahSetor);

        return response()->json([
            'total_penilaian' => $totalPenilaian,
            'rata_rata_nilai' => round($rataRata ?? 0, 1),
            'santri_aktif'    => $santriAktif,
            'progres_bulan'   => $totalBulan > 0 ? round($selesai / $totalBulan * 100) : 0,
            'selesai'         => $selesai,
            'proses'          => $proses,
            'belum'           => $belum,
            'total_bulan'     => $totalBulan,
            // Field baru untuk donut chart
            'sudah_setor'     => $sudahSetor,
            'belum_setor'     => $belumSetor,
            'total_santri'    => $totalSantri,
        ]);
    }

    public function setoranDetailBulanIni()
    {
        $bulanIni = Carbon::now();

        // Semua santri
        $semuaSantri = Santri::with('user')->get();

        // Santri yang sudah setor bulan ini (ambil tanggal terbaru per santri)
        $sudahSetorRaw = Penilaian::whereYear('tanggal', $bulanIni->year)
            ->whereMonth('tanggal', $bulanIni->month)
            ->selectRaw('santri_id, MAX(tanggal) as tanggal_setor')
            ->groupBy('santri_id')
            ->get()
            ->keyBy('santri_id');

        $sudah = [];
        $belum = [];

        foreach ($semuaSantri as $santri) {
            if ($sudahSetorRaw->has($santri->id)) {
                $sudah[] = [
                    'id'           => $santri->id,
                    'nama'         => $santri->user->name ?? '—',
                    'tanggal_setor'=> $sudahSetorRaw[$santri->id]->tanggal_setor,
                ];
            } else {
                $belum[] = [
                    'id'   => $santri->id,
                    'nama' => $santri->user->name ?? '—',
                ];
            }
        }

        return response()->json([
            'data' => [
                'sudah_setor'  => count($sudah),
                'belum_setor'  => count($belum),
                'total_santri' => $semuaSantri->count(),
                'sudah'        => $sudah,
                'belum'        => $belum,
            ]
        ]);
    }

    public function laporanPerBulan()
    {
        $penyimak = $this->getPenyimak();
        $result   = [];

        for ($i = 5; $i >= 0; $i--) {
            $bulanTarget = Carbon::now()->startOfMonth()->subMonths($i);
            $bulan       = (int) $bulanTarget->format('m');
            $tahun       = (int) $bulanTarget->format('Y');

            $base = Penilaian::with('santri.user')
                ->where('penyimak_id', $penyimak->id)
                ->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $bulan);

            $rajin = (clone $base)
                ->selectRaw('santri_id, COUNT(*) as jumlah_setoran')
                ->groupBy('santri_id')
                ->orderByDesc('jumlah_setoran')
                ->first();

            $hafalan = (clone $base)
                ->selectRaw('santri_id, SUM(nilai) as total_nilai')
                ->groupBy('santri_id')
                ->orderByDesc('total_nilai')
                ->first();

            $result[] = [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'paling_rajin' => $rajin ? [
                    'nama'  => $rajin->santri->user->name ?? '—',
                    'nilai' => (int) $rajin->jumlah_setoran,
                ] : null,
                'paling_banyak_hafalan' => $hafalan ? [
                    'nama'  => $hafalan->santri->user->name ?? '—',
                    'nilai' => (int) $hafalan->total_nilai,
                ] : null,
            ];
        }

        return response()->json($result);
    }

    public function laporanSemester()
    {
        $penyimak  = $this->getPenyimak();
        $enamBulan = Carbon::now()->subMonths(5)->startOfMonth();

        $base = Penilaian::with('santri.user')
            ->where('penyimak_id', $penyimak->id)
            ->where('tanggal', '>=', $enamBulan);

        $rajin = (clone $base)
            ->selectRaw('santri_id, COUNT(*) as jumlah_setoran')
            ->groupBy('santri_id')
            ->orderByDesc('jumlah_setoran')
            ->first();

        $hafalan = (clone $base)
            ->selectRaw('santri_id, SUM(nilai) as total_nilai')
            ->groupBy('santri_id')
            ->orderByDesc('total_nilai')
            ->first();

        return response()->json([
            'paling_rajin' => $rajin ? [
                'nama'  => $rajin->santri->user->name ?? '—',
                'nilai' => (int) $rajin->jumlah_setoran,
            ] : null,
            'paling_banyak_hafalan' => $hafalan ? [
                'nama'  => $hafalan->santri->user->name ?? '—',
                'nilai' => (int) $hafalan->total_nilai,
            ] : null,
        ]);
    }

    public function laporanPrestasiGlobal()
    {
        $now     = Carbon::now();
        $isGenap = $now->month >= 2 && $now->month <= 7;
        $tahun   = $now->year;

        if ($isGenap) {
            $start = Carbon::create($tahun, 2, 1)->startOfDay();
            $end   = Carbon::create($tahun, 7, 31)->endOfDay();
        } else {
            $startYear = $now->month >= 8 ? $tahun : $tahun - 1;
            $start     = Carbon::create($startYear, 8, 1)->startOfDay();
            $end       = Carbon::create($startYear + 1, 1, 31)->endOfDay();
        }

        $bulanList = $isGenap ? [2, 3, 4, 5, 6, 7] : [8, 9, 10, 11, 12, 1];

        $santriIds = Penilaian::whereBetween('tanggal', [$start, $end])
            ->whereNotNull('santri_id')
            ->distinct()
            ->pluck('santri_id');

        $result = [];

        foreach ($santriIds as $santriId) {
            $penilaian = Penilaian::with('santri.user')
                ->where('santri_id', $santriId)
                ->whereBetween('tanggal', [$start, $end])
                ->get();

            if ($penilaian->isEmpty()) continue;

            $nama = $penilaian->first()->santri->user->name ?? '—';

            $frekuensiBulanan = [];
            $totalAyatBulanan = [];

            foreach ($bulanList as $idx => $bulan) {
                if ($isGenap) {
                    $bulanTahun = $tahun;
                } else {
                    $bulanTahun = $bulan >= 8 ? ($now->month >= 8 ? $tahun : $tahun - 1)
                                               : ($now->month >= 8 ? $tahun + 1 : $tahun);
                }

                $bulanData = $penilaian->filter(function ($p) use ($bulan, $bulanTahun) {
                    $t = Carbon::parse($p->tanggal);
                    return $t->month === $bulan && $t->year === $bulanTahun;
                });

                $frekuensiBulanan[$idx] = $bulanData->count() > 0 ? $bulanData->count() : null;
                $totalAyatBulanan[$idx] = $bulanData->count() > 0 ? (int) $bulanData->sum('total_ayat') : null;
            }

            $result[] = [
                'id'                 => $santriId,
                'nama'               => $nama,
                'frekuensi_bulanan'  => array_values($frekuensiBulanan),
                'total_ayat_bulanan' => array_values($totalAyatBulanan),
            ];
        }

        return response()->json($result);
    }
}