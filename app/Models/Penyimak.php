<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penyimak extends Model
{
    // Nama tabel di database (sesuai yang dipakai di query SantriController)
    protected $table = 'penyimak';

    protected $fillable = [
        'user_id',
        'no_hp',
        'jenis_kelamin',
        'status_approval',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function penilaians()
    {
        return $this->hasMany(Penilaian::class);
    }
}
