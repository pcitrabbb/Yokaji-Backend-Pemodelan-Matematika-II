<?php

namespace App\Http\Controllers;

use App\Models\Catatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CatatanController extends Controller
{
    /**
     * GET /penyimak/catatan-hari-ini
     * Ambil catatan hari ini milik penyimak yang login.
     */
    public function index()
    {
        $penyimak = Auth::user()->penyimak;

        if (!$penyimak) {
            return response()->json(['isi' => '']);
        }

        $catatan = Catatan::where('penyimak_id', $penyimak->id)
            ->where('tanggal', now()->toDateString())
            ->first();

        return response()->json(['isi' => $catatan?->isi ?? '']);
    }

    /**
     * POST /penyimak/catatan-hari-ini
     * Simpan atau update catatan hari ini.
     */
    public function store(Request $request)
    {
        $penyimak = Auth::user()->penyimak;

        if (!$penyimak) {
            return response()->json(['message' => 'Penyimak tidak ditemukan'], 404);
        }

        $request->validate(['isi' => 'required|string']);

        // Upsert: update jika sudah ada, insert jika belum
        Catatan::updateOrCreate(
            [
                'penyimak_id' => $penyimak->id,
                'tanggal'     => now()->toDateString(),
            ],
            ['isi' => $request->isi]
        );

        return response()->json(['message' => 'Catatan disimpan']);
    }
}