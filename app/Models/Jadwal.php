<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    protected $fillable = [
        'judul',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'tempat',
        'tag',
        'kuota',
        'terisi',
        'kampus',
        'penyimak_id',
        'jam',
        'lokasi',
        'status',
        'kategori',
        'jenis_kelamin',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $casts = [
        // FIX: cast tanggal sebagai string 'Y-m-d', BUKAN 'date' (Carbon object)
        // 'date' cast menyebabkan Laravel return ISO datetime yang bikin timezone shift di frontend
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // FIX: accessor agar tanggal selalu return string "Y-m-d" yang konsisten
    public function getTanggalAttribute($value): string
    {
        if (!$value) return '';
        // Ambil 10 karakter pertama saja: "2025-06-15"
        return substr($value, 0, 10);
    }

    public function penyimak()
    {
        return $this->belongsTo(Penyimak::class, 'penyimak_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'jadwal_id');
    }
}