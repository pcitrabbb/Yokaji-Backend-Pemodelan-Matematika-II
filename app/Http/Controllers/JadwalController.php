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
                return [
                    'id'            => $j->id,
                    'judul'         => $j->judul,
                    'tanggal'       => $j->tanggal,
                    'waktu_mulai'   => $j->waktu_mulai,
                    'waktu_selesai' => $j->waktu_selesai,
                    'tempat'        => $j->tempat,
                    'tag'           => $j->tag,
                    'kuota'         => $j->kuota,
                    'kampus'        => $j->kampus,
                    'penyimak_id'   => $j->penyimak_id,
                    'penyimak_nama' => $j->penyimak?->nama ?? $j->penyimak?->name ?? '—',
                    'jenis_kelamin' => $j->jenis_kelamin ?? 'Semua',
                    'terisi'        => $j->bookings()->count(),
                ];
            });

        return response()->json($jadwal);
    }

    public function tersedia(Request $request)
    {
        $kampus = $request->query('kampus');

        $user = Auth::user();
        $jenisKelamin = $user?->jenis_kelamin ?? 'Semua';

        $query = DB::table('jadwals')
            ->whereRaw("DATE(tanggal) >= ?", [now()->toDateString()])
            ->whereRaw('COALESCE(terisi, 0) < COALESCE(kuota, 15)')
            ->where(function ($q) use ($jenisKelamin) {
                $q->where('jenis_kelamin', 'Semua')
                  ->orWhere('jenis_kelamin', $jenisKelamin)
                  ->orWhereNull('jenis_kelamin');
            });

        if ($kampus) {
            $query->where('kampus', $kampus);
        }

        $jadwal = $query->orderBy('tanggal')->get()->map(function ($j) {
            $penyimakNama = '-';
            if ($j->penyimak_id) {
                $penyimak = DB::table('penyimak')
                    ->join('users', 'penyimak.user_id', '=', 'users.id')
                    ->where('penyimak.id', $j->penyimak_id)
                    ->first();
                $penyimakNama = $penyimak?->nama ?? $penyimak?->name ?? '-';
            }

            $waktuMulai   = $j->waktu_mulai ?? (isset($j->jam) ? (string)$j->jam : '-');
            $waktuSelesai = $j->waktu_selesai ?? null;

            return [
                'id'            => $j->id,
                'tanggal'       => $j->tanggal,
                'waktu'         => $waktuMulai . ($waktuSelesai ? ' – ' . $waktuSelesai : ''),
                'waktu_mulai'   => $waktuMulai,
                'waktu_selesai' => $waktuSelesai,
                'penyimak_id'   => $j->penyimak_id,
                'penyimak_nama' => $penyimakNama,
                'kampus'        => $j->kampus,
                'kuota_tersisa' => ($j->kuota ?? 15) - ($j->terisi ?? 0),
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
        $jadwal = Jadwal::findOrFail($id);

        // Hapus semua booking yang terhubung dulu
        Booking::where('jadwal_id', $id)->delete();

        // Baru hapus jadwal
        $jadwal->delete();

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
                'users.nama',
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