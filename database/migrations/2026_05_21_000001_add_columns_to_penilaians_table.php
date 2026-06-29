<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penilaians', function (Blueprint $table) {
            if (!Schema::hasColumn('penilaians', 'setoran')) {
                $table->text('setoran')->nullable()->after('santri_id');
            }
            if (!Schema::hasColumn('penilaians', 'kesalahan')) {
                $table->integer('kesalahan')->nullable()->after('setoran');
            }
            if (!Schema::hasColumn('penilaians', 'status')) {
                $table->string('status')->nullable()->after('nilai');
            }
            if (!Schema::hasColumn('penilaians', 'tanggal')) {
                $table->date('tanggal')->nullable()->after('status');
            }
            if (!Schema::hasColumn('penilaians', 'total_ayat')) {
                $table->integer('total_ayat')->nullable()->after('tanggal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penilaians', function (Blueprint $table) {
            $table->dropColumn(['setoran', 'kesalahan', 'status', 'tanggal', 'total_ayat']);
        });
    }
};