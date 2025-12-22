<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('laporan', function (Blueprint $table) {
            $table->id()->primary();
            $table->string('nama_pengusul', 100);
            $table->string('email');
            $table->string('nomor_telepon', 15);
            $table->string('lokasi_kerusakan');
            $table->string('deskripsi_kerusakan');
            $table->string('foto_kerusakan');
            $table->enum('status_laporan', ['menunggu', 'diproses', 'ditolak','terselesaikan'])->default('menunggu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan', function (Blueprint $table) {
            //
        });
    }
};
