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
        Schema::table('laporan', function (Blueprint $table) {
            // Tambahkan kolom admin
            $table->string('disetujui_oleh')->nullable();
            $table->timestamp('disetujui_pada')->nullable();
            $table->string('ditolak_oleh')->nullable();
            $table->timestamp('ditolak_pada')->nullable();
            $table->text('alasan_ditolak')->nullable();
            $table->string('diselesaikan_oleh')->nullable();
            $table->timestamp('diselesaikan_pada')->nullable();
            $table->string('bukti_penyelesaian')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan', function (Blueprint $table) {
            $table->dropColumn([
                'disetujui_oleh',
                'disetujui_pada',
                'ditolak_oleh',
                'ditolak_pada',
                'alasan_ditolak',
                'diselesaikan_oleh',
                'diselesaikan_pada',
                'bukti_penyelesaian'
            ]);
        });
    }
};
