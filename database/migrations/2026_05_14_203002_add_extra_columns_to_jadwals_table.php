<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {
            if (!Schema::hasColumn('jadwals', 'tag'))           $table->string('tag')->nullable();
            if (!Schema::hasColumn('jadwals', 'tempat'))        $table->string('tempat')->nullable();
            if (!Schema::hasColumn('jadwals', 'kuota'))         $table->integer('kuota')->default(15);
            if (!Schema::hasColumn('jadwals', 'terisi'))        $table->integer('terisi')->default(0);
            if (!Schema::hasColumn('jadwals', 'waktu_selesai')) $table->string('waktu_selesai')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {
            $table->dropColumn(['tag', 'tempat', 'kuota', 'terisi', 'waktu_selesai']);
        });
    }
};