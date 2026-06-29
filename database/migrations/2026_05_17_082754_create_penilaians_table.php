<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('penilaians')) {
            Schema::create('penilaians', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('penyimak_id')->nullable();
                $table->unsignedBigInteger('santri_id')->nullable();
                $table->string('nilai')->nullable();
                $table->text('catatan')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaians');
    }
};