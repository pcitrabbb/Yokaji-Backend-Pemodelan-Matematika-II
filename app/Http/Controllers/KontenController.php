<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class JadwalController extends Controller
{
    public function index()
    {
        $jadwal = Jadwal::with('penyimak')
            ->orderBy('tanggal', 'asc')
            ->get()
            ->map(function ($j) {
                // FIX: hitung terisi dari relasi bookings, bukan kolom terisi
                // agar data selalu akurat meski kolom terisi tidak di-sync
                $terisi = $j->bookings()->count();

                return [
                    'id'            => $j->id,
                    'judul'         => $j->judul,
                    // FIX: tanggal sudah dijamin string "Y-m-d" dari accessor model
                    'tanggal'       => $j->tanggal,
                    'waktu_mulai'   => $j->waktu_mulai,
                    'waktu_selesai' => $j->waktu_selesai,
                    'tempat'        => $j->tempat,
                    'tag'           => $j->tag,
                    'kuota'         => $j->kuota,
                    'kampus'        => $j->kampus,
                    'penyimak_id'   => $j->penyimak_id,
                    // FIX: coba ambil nama dari berbagai kemungkinan field di relasi
                    'penyimak_nama' => $j->penyimak?->nama
                                    ?? $j->penyimak?->name
                                    ?? $j->penyimak?->user?->nama
                                    ?? $j->penyimak?->user?->name
                                    ?? '—',
                    'jenis_kelamin' => $j->jenis_kelamin ?? 'Semua',
                    'terisi'        => $terisi,
                ];
            });

        return response()->json($jadwal);
    }

    public function tersedia(Request $request)
    {
        $kampus = $request->query('kampus');

        $user         = Auth::user();
        $jenisKelamin = $user?->jenis_kelamin ?? 'Semua';

        // FIX: subquery hitung jumlah booking per jadwal agar tidak bergantung kolom terisi
        // yang bisa stale jika tidak di-update saat booking dibuat/dihapus
        $query = DB::table('jadwals')
            ->leftJoin(
                DB::raw('(SELECT jadwal_id, COUNT(*) as booking_count FROM bookings GROUP BY jadwal_id) as bc'),
                'jadwals.id', '=', 'bc.jadwal_id'
            )
            ->whereRaw("DATE(jadwals.tanggal) >= ?", [now()->toDateString()])
            // FIX: pakai booking_count aktual, bukan kolom terisi
            ->whereRaw('COALESCE(bc.booking_count, 0) < COALESCE(jadwals.kuota, 15)')
            ->where(function ($q) use ($jenisKelamin) {
                $q->where('jadwals.jenis_kelamin', 'Semua')
                  ->orWhere('jadwals.jenis_kelamin', $jenisKelamin)
                  ->orWhereNull('jadwals.jenis_kelamin');
            })
            ->select('jadwals.*', DB::raw('COALESCE(bc.booking_count, 0) as booking_count'));

        if ($kampus) {
            $query->where('jadwals.kampus', $kampus);
        }

        $jadwal = $query->orderBy('jadwals.tanggal')->get()->map(function ($j) {
            // FIX: ambil nama penyimak via join agar tidak ada N+1 query
            $penyimakNama = '—';
            if ($j->penyimak_id) {
                // Coba tabel penyimaks → users.nama, fallback ke users.name
                $penyimakNama = DB::table('penyimaks')
                    ->join('users', 'penyimaks.user_id', '=', 'users.id')
                    ->where('penyimaks.id', $j->penyimak_id)
                    ->selectRaw('COALESCE(users.nama, users.name) as nama')
                    ->value('nama') ?? '—';
            }

            $waktuMulai   = $j->waktu_mulai ?? (isset($j->jam) ? (string)$j->jam : '-');
            $waktuSelesai = $j->waktu_selesai ?? null;

            // FIX: pastikan tanggal return string "Y-m-d" bukan datetime/Carbon
            $tanggal = $j->tanggal ? substr($j->tanggal, 0, 10) : null;

            return [
                'id'            => $j->id,
                'tanggal'       => $tanggal,
                'waktu'         => $waktuMulai . ($waktuSelesai ? ' – ' . $waktuSelesai : ''),
                'waktu_mulai'   => $waktuMulai,
                'waktu_selesai' => $waktuSelesai,
                'penyimak_id'   => $j->penyimak_id,
                'penyimak_nama' => $penyimakNama,
                'kampus'        => $j->kampus,
                // FIX: kuota_tersisa pakai booking_count aktual
                'kuota_tersisa' => ($j->kuota ?? 15) - $j->booking_count,
                'jenis_kelamin' => $j->jenis_kelamin ?? 'Semua',
            ];
        });

        return response()->json($jadwal);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal'     => 'required|date',
            'waktu_mulai' => 'required|string',
            'kuota'       => 'nullable|integer',
        ]);

        $jadwal = Jadwal::create([
            'judul'         => $request->judul         ?? null,
            'tanggal'       => $request->tanggal,
            'waktu_mulai'   => $request->waktu_mulai,
            'waktu_selesai' => $request->waktu_selesai ?? null,
            'tempat'        => $request->tempat        ?? null,
            'tag'           => $request->tag           ?? null,
            'kuota'         => $request->kuota         ?? 15,
            'kampus'        => $request->kampus        ?? null,
            'penyimak_id'   => $request->penyimak_id   ?? null,
            'jenis_kelamin' => $request->jenis_kelamin ?? 'Semua',
            'terisi'        => 0,
        ]);

        return response()->json($jadwal, 201);
    }

    public function update(Request $request, $id)
    {
        $jadwal = Jadwal::findOrFail($id);

        $jadwal->update($request->only([
            'judul', 'tanggal', 'waktu_mulai', 'waktu_selesai',
            'tempat', 'tag', 'kuota', 'kampus', 'penyimak_id', 'jenis_kelamin',
        ]));

        return response()->json($jadwal);
    }

    public function destroy($id)
    {
        Jadwal::findOrFail($id)->delete();
        return response()->json(['message' => 'Jadwal dihapus.']);
    }

    public function peserta($id)
    {
        Jadwal::findOrFail($id);

        $peserta = DB::table('bookings')
            ->join('santri', 'bookings.santri_id', '=', 'santri.id')
            ->join('users', 'santri.user_id', '=', 'users.id')
            ->where('bookings.jadwal_id', $id)
            ->select([
                'bookings.id as booking_id',
                'bookings.id',
                // FIX: ambil nama dari users.nama atau users.name
                DB::raw('COALESCE(users.nama, users.name) as nama'),
                'users.email',
                'santri.nim',
                'santri.fakultas',
                'santri.prodi',
                'bookings.created_at',
            ])
            ->get()
            ->map(function ($p) {
                return [
                    'id'         => $p->id,
                    'booking_id' => $p->booking_id,
                    'nama'       => $p->nama     ?? '-',
                    'nim'        => $p->nim      ?? null,
                    'email'      => $p->email    ?? null,
                    'fakultas'   => $p->fakultas ?? null,
                    'prodi'      => $p->prodi    ?? null,
                    'no_hp'      => null,
                    'created_at' => $p->created_at,
                ];
            });

        return response()->json($peserta);
    }
}