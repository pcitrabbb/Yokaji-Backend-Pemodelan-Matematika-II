<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Santri extends Model {
    protected $table = 'santri';
    protected $fillable = [
        'user_id', 'nim', 'fakultas',
        'prodi', 'jumlah_hafalan',
        'ktm', 'status_approval'
    ];

    public function user() {
        return $this->belongsTo(User::class);

    }

    public function penilaians()
{
    return $this->hasMany(Penilaian::class);
}
}


