<?php

namespace App\Http\Controllers;

use App\Models\Penilaian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Controller di app/Http/Controllers/PenilaianController.php
// (bukan yang di Api/Penyimak)

class PenilaianController extends Controller
{
    public function store(Request $request)
    {
        // Validasi manual pakai DB agar tidak error "santris does not exist"
        $santriAda = DB::table('santri')->where('id', $request->santri_id)->exists();
        if (!$request->santri_id || !$santriAda) {
            return response()->json(['message' => 'Santri tidak ditemukan'], 422);
        }
        if (!$request->nilai || !is_numeric($request->nilai)) {
            return response()->json(['message' => 'Nilai tidak valid'], 422);
        }
        if (!$request->status) {
            return response()->json(['message' => 'Status wajib diisi'], 422);
        }

        $penyimak = Auth::user()->penyimak;

        if (!$penyimak) {
            return response()->json(['message' => 'Penyimak tidak ditemukan'], 404);
        }

        $penilaian = Penilaian::create([
            'penyimak_id' => $penyimak->id,
            'santri_id'   => $request->santri_id,
            'setoran'     => $request->setoran,
            'kesalahan'   => $request->kesalahan,
            'nilai'       => $request->nilai,
            'status'      => $request->status,
            'catatan'     => $request->catatan,
            'tanggal'     => $request->tanggal ?? now()->toDateString(),
            'total_ayat'  => $request->total_ayat ?? 0,
        ]);

        return response()->json([
            'message' => 'Penilaian berhasil disimpan',
            'data'    => $penilaian,
        ], 201);
    }
}