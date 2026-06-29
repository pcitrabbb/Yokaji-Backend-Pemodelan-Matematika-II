<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Catatan extends Model
{
    protected $table = 'catatan_harian';

    protected $fillable = [
        'penyimak_id',
        'tanggal',
        'isi',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function penyimak()
    {
        return $this->belongsTo(Penyimak::class);
    }
}