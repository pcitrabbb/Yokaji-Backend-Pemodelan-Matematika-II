<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Jadwal;
use App\Models\Santri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $user   = Auth::user();
        $santri = Santri::where('user_id', $user->id)->first();

        if (!$santri) {
            return response()->json(['message' => 'Data santri tidak ditemukan.'], 404);
        }

        $request->validate([
            'jadwal_id' => 'required|exists:jadwals,id',
        ]);

        $jadwal = Jadwal::findOrFail($request->jadwal_id);

        // Cek kuota
        if (($jadwal->terisi ?? 0) >= ($jadwal->kuota ?? 15)) {
            return response()->json(['message' => 'Kuota sesi ini sudah penuh.'], 422);
        }

        // Cek sudah booking belum
        $sudah = DB::table('bookings')
            ->where('santri_id', $santri->id)
            ->where('jadwal_id', $jadwal->id)
            ->exists();

        if ($sudah) {
            return response()->json(['message' => 'Kamu sudah booking sesi ini.'], 422);
        }

        // Insert hanya kolom yang pasti ada
        $bookingId = DB::table('bookings')->insertGetId([
            'santri_id'  => $santri->id,
            'jadwal_id'  => $jadwal->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update terisi
        DB::table('jadwals')
            ->where('id', $jadwal->id)
            ->increment('terisi');

        return response()->json(['id' => $bookingId, 'message' => 'Booking berhasil!'], 201);
    }

    public function milikSaya(Request $request)
    {
        $user   = Auth::user();
        $santri = Santri::where('user_id', $user->id)->first();

        if (!$santri) {
            return response()->json([]);
        }

        $bookings = DB::table('bookings')
            ->join('jadwals', 'bookings.jadwal_id', '=', 'jadwals.id')
            ->where('bookings.santri_id', $santri->id)
            ->select('bookings.*', 'jadwals.tanggal', 'jadwals.waktu_mulai')
            ->get();

        return response()->json($bookings);
    }

    public function destroy($id)
    {
        $booking = DB::table('bookings')->where('id', $id)->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking tidak ditemukan.'], 404);
        }

        // Kurangi terisi
        DB::table('jadwals')
            ->where('id', $booking->jadwal_id)
            ->decrement('terisi');

        DB::table('bookings')->where('id', $id)->delete();

        return response()->json(['message' => 'Booking dihapus.']);
    }
}