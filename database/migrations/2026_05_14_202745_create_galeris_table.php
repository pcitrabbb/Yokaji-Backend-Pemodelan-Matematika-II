<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('galeris', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('keterangan')->nullable();
            $table->string('kategori')->nullable();
            $table->string('url_foto');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('galeris');
    }
};