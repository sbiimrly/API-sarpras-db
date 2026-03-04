<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Hapus dulu jika ada (clean up)
        Schema::dropIfExists('activity_logs');

        // Buat tabel baru
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable(); // nullable untuk laporan dari user
            $table->string('activity');
            $table->string('type'); // 'laporan_status', 'laporan_create', 'laporan_archive', etc
            $table->json('details')->nullable();
            $table->unsignedBigInteger('laporan_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            // Foreign key ke tabel admins
            $table->foreign('admin_id')->references('id')->on('admin')->onDelete('set null');

            // Foreign key ke tabel laporan
            $table->foreign('laporan_id')->references('id')->on('laporan')->onDelete('set null');

            $table->index(['admin_id', 'is_read', 'created_at']);
            $table->index(['laporan_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
};
