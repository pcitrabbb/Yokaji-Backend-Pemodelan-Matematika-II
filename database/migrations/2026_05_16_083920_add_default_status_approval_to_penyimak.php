<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penyimak', function (Blueprint $table) {
            $table->string('status_approval')->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('penyimak', function (Blueprint $table) {
            $table->string('status_approval')->nullable()->default(null)->change();
        });
    }
};