<?php

namespace App\Http\Controllers;

use App\Models\Penyimak;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PenyimakController extends Controller
{
    public function index()
    {
        $penyimak = Penyimak::with('user')->get();

        $result = $penyimak->map(function ($p) {
            return [
                'id'           => $p->id,
                'user_id'      => $p->user_id,
                'nama'         => $p->user->nama ?? $p->user->name ?? '-',
                'email'        => $p->user->email ?? '-',
                'no_hp'        => $p->no_hp        ?? null,
                'gelar'        => $p->gelar        ?? null,
                'hafalan'      => $p->hafalan      ?? null,
                'pengalaman'   => $p->pengalaman   ?? null,
                'ketersediaan' => $p->ketersediaan ?? null,
                'status'       => $p->status_approval ?? 'pending',
                'approved'     => $p->status_approval === 'approved',
                'created_at'   => $p->created_at,
            ];
        });

        return response()->json($result);
    }

    // Profil penyimak yang sedang login
    public function profile(Request $request)
    {
        $user     = $request->user();
        $penyimak = Penyimak::where('user_id', $user->id)->first();

        return response()->json([
            'data' => [
                'id'            => $penyimak?->id,
                'user_id'       => $user->id,
                'nama'          => $user->name,
                'email'         => $user->email,
                'no_hp'         => $penyimak?->no_hp,
                'jenis_kelamin' => $user->jenis_kelamin ?? $penyimak?->jenis_kelamin,
                'status'        => $penyimak?->status_approval,
                'created_at'    => $penyimak?->created_at ?? $user->created_at,
            ]
        ]);
    }

    // Semua santri yang sudah approved
    public function santriDibimbing(Request $request)
    {
        $santri = \App\Models\Santri::with('user')
            ->where('status_approval', 'approved')
            ->get()
            ->map(function ($s) {
                return [
                    'id'             => $s->id,
                    'nama'           => $s->user->name ?? '-',
                    'nim'            => $s->nim,
                    'fakultas'       => $s->fakultas,
                    'jumlah_hafalan' => $s->jumlah_hafalan,
                    'juz_sekarang'   => $s->jumlah_hafalan,
                    'progres'        => 0,
                ];
            });

        return response()->json(['data' => $santri]);
    }

    public function list()
    {
        $penyimak = Penyimak::with('user')
            ->where('status_approval', 'approved')
            ->get()
            ->map(function ($p) {
                return [
                    'id'   => $p->id,
                    'nama' => $p->user->name ?? '-',
                ];
            });

        return response()->json(['data' => $penyimak]);
    }

    public function approve($id)
    {
        $penyimak = Penyimak::findOrFail($id);
        $penyimak->status_approval = 'approved';
        $penyimak->save();
        return response()->json(['message' => 'Penyimak berhasil diapprove']);
    }

    public function reject($id)
    {
        $penyimak = Penyimak::findOrFail($id);
        $penyimak->status_approval = 'rejected';
        $penyimak->save();
        return response()->json(['message' => 'Penyimak berhasil ditolak']);
    }

    public function destroy($id)
    {
        $penyimak = Penyimak::findOrFail($id);
        $userId   = $penyimak->user_id;
        $penyimak->delete();
        User::find($userId)?->delete();
        return response()->json(['message' => 'Penyimak berhasil dihapus']);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'password_lama' => 'required',
            'password_baru'  => 'required|min:8',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password_lama, $user->password)) {
            return response()->json(['message' => 'Password lama salah.'], 422);
        }

        $user->update(['password' => Hash::make($request->password_baru)]);

        return response()->json(['message' => 'Password berhasil diubah.']);
    }

    public function hapusAkun(Request $request)
    {
        $user     = $request->user();
        $penyimak = Penyimak::where('user_id', $user->id)->first();

        if ($penyimak) {
            $penyimak->delete();
        }

        $user->delete();

        return response()->json(['message' => 'Akun berhasil dihapus.']);
    }
}