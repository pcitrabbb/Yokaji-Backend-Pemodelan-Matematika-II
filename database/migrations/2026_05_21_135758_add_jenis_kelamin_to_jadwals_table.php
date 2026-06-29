<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom sudah ada di database, skip agar tidak error "duplicate column"
        if (!Schema::hasColumn('jadwals', 'jenis_kelamin')) {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->string('jenis_kelamin')->default('Semua');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('jadwals', 'jenis_kelamin')) {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->dropColumn('jenis_kelamin');
            });
        }
    }
};