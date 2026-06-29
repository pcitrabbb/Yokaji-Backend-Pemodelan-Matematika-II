<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {
            // Cek dulu kolom mana yang belum ada, tambahkan semuanya
            if (!Schema::hasColumn('jadwals', 'judul')) {
                $table->string('judul')->nullable()->after('id');
            }
            if (!Schema::hasColumn('jadwals', 'kampus')) {
                $table->string('kampus')->nullable()->after('tempat');
            }
            if (!Schema::hasColumn('jadwals', 'penyimak_id')) {
                $table->unsignedBigInteger('penyimak_id')->nullable()->after('kampus');
            }
            if (!Schema::hasColumn('jadwals', 'tag')) {
                $table->string('tag')->nullable()->after('kuota');
            }
            if (!Schema::hasColumn('jadwals', 'terisi')) {
                $table->integer('terisi')->default(0)->after('kuota');
            }
            if (!Schema::hasColumn('jadwals', 'waktu_selesai')) {
                $table->string('waktu_selesai')->nullable()->after('waktu_mulai');
            }
            if (!Schema::hasColumn('jadwals', 'tempat')) {
                $table->string('tempat')->nullable()->after('waktu_selesai');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {
            $table->dropColumn([
                'judul', 'kampus', 'penyimak_id', 'tag',
                'terisi', 'waktu_selesai', 'tempat',
            ]);
        });
    }
};