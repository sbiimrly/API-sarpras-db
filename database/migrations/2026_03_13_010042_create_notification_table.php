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
        Schema::create('notification', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Admin yang menerima notif
            $table->foreignId('laporan_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('type'); // 'status_change', 'new_report', 'archive', etc
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Data tambahan
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification');
    }
};
