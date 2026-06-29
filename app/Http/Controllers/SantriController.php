<?php

namespace App\Http\Controllers;

use App\Models\Santri;
use App\Models\User;
use App\Models\Penilaian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SantriController extends Controller
{
    public function index()
    {
        $santri = Santri::with('user')->get();

        $result = $santri->map(function ($s) {
            return [
                'id'          => $s->id,
                'user_id'     => $s->user_id,
                'nama'        => $s->user->name     ?? '-',
                'email'       => $s->user->email    ?? '-',
                'nim' => $s->nim ?? null,
                'no_hp'       => $s->no_hp          ?? null,
                'fakultas'    => $s->fakultas       ?? null,
                'prodi'       => $s->prodi          ?? null,
                'juz_hafalan' => $s->juz_hafalan    ?? null,
                'jadwal_id'   => $s->jadwal_id      ?? null,
                'motivasi'    => $s->motivasi       ?? null,
                'status'      => $s->status_approval ?? 'pending',
                'approved'    => $s->status_approval === 'approved',
                'created_at'  => $s->created_at,
            ];
        });

        return response()->json($result);
    }

    public function show($id)
    {
        $s = Santri::with('user')->findOrFail($id);
        return response()->json([
            'id'          => $s->id,
            'user_id'     => $s->user_id,
            'nama'        => $s->user->name     ?? '-',
            'email'       => $s->user->email    ?? '-',
            'nim'         => $s->nim            ?? null,
            'no_hp'       => $s->no_hp          ?? null,
            'fakultas'    => $s->fakultas       ?? null,
            'prodi'       => $s->prodi          ?? null,
            'juz_hafalan' => $s->juz_hafalan    ?? null,
            'jadwal_id'   => $s->jadwal_id      ?? null,
            'motivasi'    => $s->motivasi       ?? null,
            'status'      => $s->status_approval ?? 'pending',
            'approved'    => $s->status_approval === 'approved',
            'created_at'  => $s->created_at,
        ]);
    }

    public function approve($id)
    {
        $santri = Santri::findOrFail($id);
        $santri->status_approval = 'approved';
        $santri->save();
        return response()->json(['message' => 'Santri berhasil diapprove']);
    }

    public function destroy($id)
    {
        $santri = Santri::findOrFail($id);
        $userId = $santri->user_id;
        $santri->delete();
        User::find($userId)?->delete();
        return response()->json(['message' => 'Santri berhasil dihapus']);
    }

    public function ringkasan()
    {
        $user   = Auth::user();
        $santri = Santri::where('user_id', $user->id)->first();

        if (!$santri) {
            return response()->json([
                'data' => [
                    'total_setoran'   => 0,
                    'nilai_rata_rata' => 0,
                    'hafalan_juz'     => 0,
                    'progres_persen'  => 0,
                    'juz_selesai'     => 0,
                    'juz_sedang'      => 0,
                ]
            ]);
        }

        $penilaians    = Penilaian::where('santri_id', $santri->id)->get();
        $totalSetoran  = $penilaians->count();
        $nilaiRataRata = $penilaians->whereNotNull('nilai')->avg('nilai') ?? 0;
        $totalAyat     = $penilaians->sum('total_ayat');

        // Estimasi juz dari total ayat (1 juz ≈ 604 ayat)
        // Fallback ke kolom jumlah_hafalan di tabel santri jika belum ada penilaian
        $estimasiJuz = $totalAyat > 0
            ? round($totalAyat / 604, 2)
            : ($santri->jumlah_hafalan ?? 0);

        $progres = min(100, round(($estimasiJuz / 30) * 100, 1));

        return response()->json([
            'data' => [
                'total_setoran'   => $totalSetoran,
                'nilai_rata_rata' => round($nilaiRataRata, 1),
                'hafalan_juz'     => $estimasiJuz,
                'progres_persen'  => $progres,
                'juz_selesai'     => floor($estimasiJuz),
                'juz_sedang'      => min(30, (int) ceil($estimasiJuz)),
            ]
        ]);
    }

    public function jadwalSaya()
    {
        $user   = Auth::user();
        $santri = Santri::where('user_id', $user->id)->first();

        if (!$santri) {
            return response()->json(['data' => []]);
        }

        $bookings = DB::table('bookings')
            ->join('jadwals', 'bookings.jadwal_id', '=', 'jadwals.id')
            ->where('bookings.santri_id', $santri->id)
            ->select([
                'bookings.id',
                'jadwals.id as jadwal_id',
                'jadwals.tanggal',
                'jadwals.waktu_mulai',
                'jadwals.jam',
                'jadwals.waktu_selesai',
                'jadwals.tempat',
                'jadwals.lokasi',
                'jadwals.tag',
                'jadwals.kategori',
                'jadwals.judul',
                'jadwals.penyimak_id',
                'jadwals.jenis_kelamin',
                'bookings.status as status_booking',
            ])
            ->get();

        $result = $bookings->map(function ($b) {
            $waktuMulai   = $b->waktu_mulai ?? ($b->jam ? (string)$b->jam : '-');
            $waktuSelesai = $b->waktu_selesai ?? '-';
            $tempat       = $b->tempat ?? $b->lokasi ?? null;
            $jenis        = $b->tag ?? $b->kategori ?? 'Setoran Hafalan';
            $detail       = $b->judul ?? $b->kategori ?? 'Setoran Hafalan';

            // ✅ FIX: nama tabel 'penyimak' (tanpa s), coba kolom 'nama' lalu fallback ke 'name'
            $penyimakNama = '-';
            if ($b->penyimak_id) {
                $penyimak = DB::table('penyimak')
                    ->join('users', 'penyimak.user_id', '=', 'users.id')
                    ->where('penyimak.id', $b->penyimak_id)
                    ->select('users.nama', 'users.name')
                    ->first();

                $penyimakNama = $penyimak?->nama
                             ?? $penyimak?->name
                             ?? '-';
            }

            return [
                'id'            => $b->id,
                'tanggal'       => $b->tanggal,
                'waktu_mulai'   => $waktuMulai,
                'waktu_selesai' => $waktuSelesai,
                'jenis'         => $jenis,
                'detail'        => $detail,
                'penyimak'      => $penyimakNama,      // tetap ada untuk kompatibilitas
                'penyimak_nama' => $penyimakNama,      // ✅ FIX: key yang dibaca frontend
                'tempat'        => $tempat,
                'jenis_kelamin' => $b->jenis_kelamin ?? null,
                'status'        => $b->status_booking ?? 'Terjadwal',
            ];
        });

        return response()->json(['data' => $result]);
    }

    public function historySetoran()
    {
        $user   = Auth::user();
        $santri = Santri::where('user_id', $user->id)->first();

        if (!$santri) {
            return response()->json(['data' => []]);
        }

        $limit = request()->query('limit');

        $query = Penilaian::with(['penyimak.user'])
            ->where('santri_id', $santri->id)
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit((int) $limit);
        }

        $data = $query->get()->map(function ($p) {
            // Ambil nama penyimak dari relasi user, fallback ke nama langsung
            $penyimakNama = $p->penyimak?->user?->nama
                         ?? $p->penyimak?->user?->name
                         ?? $p->penyimak?->nama
                         ?? '—';

            return [
                'id'            => $p->id,
                'tanggal'       => $p->tanggal?->toDateString() ?? $p->created_at->toDateString(),
                'jenis_setoran' => $p->status   ?? 'Setoran',
                'detail'        => $p->setoran  ?? '—',
                'ustadz'        => $penyimakNama,
                'nilai'         => $p->nilai !== null ? (int) $p->nilai : null,
                'catatan'       => $p->catatan,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function ubahPassword(Request $request)
    {
        $request->validate([
            'password_lama' => 'required',
            'password_baru'  => 'required|min:8',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->password_lama, $user->password)) {
            return response()->json(['message' => 'Password lama salah.'], 422);
        }

        $user->update(['password' => Hash::make($request->password_baru)]);

        return response()->json(['message' => 'Password berhasil diubah.']);
    }

    public function hapusAkun()
    {
        $user   = Auth::user();
        $santri = Santri::where('user_id', $user->id)->first();

        if ($santri) {
            // Hapus bookings & setorans terkait dulu agar tidak ada foreign key constraint
            $bookingIds = DB::table('bookings')->where('santri_id', $santri->id)->pluck('id');
            if ($bookingIds->isNotEmpty()) {
                DB::table('setorans')->whereIn('booking_id', $bookingIds)->delete();
                DB::table('bookings')->whereIn('id', $bookingIds)->delete();
            }
            $santri->delete();
        }

        $user->delete();

        return response()->json(['message' => 'Akun berhasil dihapus.']);
    }
}