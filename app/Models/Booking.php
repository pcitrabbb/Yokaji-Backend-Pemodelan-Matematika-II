<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = ['jadwal_id', 'santri_id', 'status'];

    public function santri()
    {
        return $this->belongsTo(Santri::class, 'santri_id');
    }

    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_id');
    }
}