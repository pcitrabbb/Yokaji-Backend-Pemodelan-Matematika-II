<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('catatan_harian')) {
            Schema::create('catatan_harian', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('penyimak_id');
                $table->date('tanggal');
                $table->text('isi');
                $table->timestamps();

                $table->unique(['penyimak_id', 'tanggal']); // 1 catatan per hari per penyimak
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('catatan_harian');
    }
};