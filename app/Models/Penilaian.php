<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penilaian extends Model
{
    protected $fillable = [
        'penyimak_id',
        'santri_id',
        'setoran',
        'kesalahan',
        'nilai',
        'status',
        'catatan',
        'tanggal',
        'total_ayat',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function santri()
    {
        return $this->belongsTo(Santri::class);
    }

    public function penyimak()
    {
        return $this->belongsTo(Penyimak::class);
    }
}