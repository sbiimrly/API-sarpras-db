<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('laporan', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->integer('archived_by')->nullable(); // ID admin yang mengarsip
        });
    }

    public function down()
    {
        Schema::table('laporan', function (Blueprint $table) {
            $table->dropColumn(['is_archived', 'archived_at', 'archived_by']);
        });
    }
};
