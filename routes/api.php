<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SantriController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\SetoranController;
use App\Http\Controllers\PenyimakController;
use App\Http\Controllers\CatatanController;
use App\Http\Controllers\Api\Penyimak\PenilaianController as PenyimakPenilaianController;
use App\Http\Controllers\PenilaianController;
use App\Http\Controllers\GaleriController;
use App\Http\Controllers\KontenController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

// ── Route publik (tanpa auth) ──────────────────────────────────
Route::post('/register',          [AuthController::class, 'register']);
Route::post('/register-penyimak', [AuthController::class, 'registerPenyimak']);
Route::post('/login',             [AuthController::class, 'login']);
Route::get('/check-status',       [AuthController::class, 'checkStatus']);
Route::get('/galeri',             [GaleriController::class, 'index']);
Route::get('/konten',             [KontenController::class, 'index']);

// Forgot Password
Route::post('/forgot-password',       [PasswordResetController::class, 'sendCode']);
Route::post('/forgot-password/reset', [PasswordResetController::class, 'resetPassword']);

// ── Admin panel routes (pakai X-Admin-Key header) ──────────────
Route::middleware('admin.key')->group(function () {
    Route::get('/admin/santri',                [SantriController::class, 'index']);
    Route::put('/admin/santri/{id}/approve',   [SantriController::class, 'approve']);
    Route::delete('/admin/santri/{id}',        [SantriController::class, 'destroy']);

    Route::get('/admin/penyimak',              [PenyimakController::class, 'index']);
    Route::put('/admin/penyimak/{id}/approve', [PenyimakController::class, 'approve']);
    Route::put('/admin/penyimak/{id}/reject',  [PenyimakController::class, 'reject']);
    Route::delete('/admin/penyimak/{id}',      [PenyimakController::class, 'destroy']);

    Route::get('/admin/jadwal',                [JadwalController::class, 'index']);
    Route::post('/admin/jadwal',               [JadwalController::class, 'store']);
    Route::put('/admin/jadwal/{id}',           [JadwalController::class, 'update']);
    Route::delete('/admin/jadwal/{id}',        [JadwalController::class, 'destroy']);
    Route::get('/admin/jadwal/{id}/peserta',   [JadwalController::class, 'peserta']);
    Route::delete('/admin/booking/{id}',       [BookingController::class, 'destroy']);

    Route::post('/galeri',                     [GaleriController::class, 'store']);
    Route::delete('/galeri/{id}',              [GaleriController::class, 'destroy']);

    Route::put('/konten',                      [KontenController::class, 'update']);
});

// ── Route yang butuh login ─────────────────────────────────────
Route::middleware('auth:api')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // santri
    Route::get('/santri/ringkasan',       [SantriController::class, 'ringkasan']);
    Route::get('/santri/jadwal',          [SantriController::class, 'jadwalSaya']);
    Route::get('/santri/history-setoran', [SantriController::class, 'historySetoran']);
    Route::get('/santri',                 [SantriController::class, 'index']);
    Route::get('/santri/{id}',            [SantriController::class, 'show']);
    Route::put('/santri/{id}/approve',    [SantriController::class, 'approve']);
    Route::delete('/santri/{id}',         [SantriController::class, 'destroy']);

    // penyimak - management
    Route::get('/penyimak',              [PenyimakController::class, 'index']);
    Route::get('/penyimak/list',         [PenyimakController::class, 'list']);
    Route::put('/penyimak/{id}/approve', [PenyimakController::class, 'approve']);
    Route::delete('/penyimak/{id}',      [PenyimakController::class, 'destroy']);

    // penyimak - dashboard & fitur
    Route::get('/penyimak/profile',              [PenyimakController::class, 'profile']);
    Route::get('/penyimak/santri-aktif',         [PenyimakController::class, 'santriDibimbing']);
    Route::get('/penyimak/catatan-hari-ini',     [CatatanController::class, 'index']);
    Route::post('/penyimak/catatan-hari-ini',    [CatatanController::class, 'store']);
    Route::get('/penyimak/dashboard/stats',      [PenyimakPenilaianController::class, 'stats']);
    Route::get('/penyimak/penilaian/count',      [PenyimakPenilaianController::class, 'count']);
    Route::get('/penyimak/penilaian',            [PenyimakPenilaianController::class, 'index']);
    Route::post('/penyimak/penilaian',           [PenyimakPenilaianController::class, 'store']);
    Route::put('/penyimak/penilaian/{id}',       [PenyimakPenilaianController::class, 'update']);
    Route::get('/penyimak/laporan/per-bulan',    [PenyimakPenilaianController::class, 'laporanPerBulan']);
    Route::get('/penyimak/laporan/semester',     [PenyimakPenilaianController::class, 'laporanSemester']);
    Route::get('/laporan/prestasi-global',       [PenyimakPenilaianController::class, 'laporanPrestasiGlobal']);
    Route::post('/penyimak/change-password',     [PenyimakController::class, 'changePassword']);
    Route::delete('/penyimak/account',           [PenyimakController::class, 'hapusAkun']);

    // jadwal
    Route::get('/jadwal',          [JadwalController::class, 'index']);
    Route::post('/jadwal',         [JadwalController::class, 'store']);
    Route::put('/jadwal/{id}',     [JadwalController::class, 'update']);
    Route::delete('/jadwal/{id}',  [JadwalController::class, 'destroy']);
    Route::get('/jadwal-tersedia', [JadwalController::class, 'tersedia']);

    // booking
    Route::post('/booking',     [BookingController::class, 'store']);
    Route::get('/booking/saya', [BookingController::class, 'milikSaya']);

    // santri - akun
    Route::post('/santri/ubah-password', [SantriController::class, 'ubahPassword']);
    Route::delete('/santri/hapus-akun',  [SantriController::class, 'hapusAkun']);

    // setoran — global-stats HARUS sebelum route dinamis /{santri_id}
    Route::get('/setoran/global-stats', [SetoranController::class, 'globalStats']);
    Route::get('/setoran/global-stats/detail', [PenyimakPenilaianController::class, 'setoranDetailBulanIni']);
    Route::post('/setoran',             [SetoranController::class, 'store']);
    Route::get('/riwayat/{santri_id}',  [SetoranController::class, 'riwayat']);

    // penilaian (legacy controller — kosong, bisa dihapus)
    // Route::post('/penilaian', [PenilaianController::class, 'store']);
});