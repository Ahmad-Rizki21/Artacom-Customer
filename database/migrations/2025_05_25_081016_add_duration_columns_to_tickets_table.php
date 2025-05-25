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
        Schema::table('tickets', function (Blueprint $table) {
            // Tambahkan kolom untuk menyimpan durasi waktu
            if (!Schema::hasColumn('tickets', 'open_duration_seconds')) {
                $table->integer('open_duration_seconds')->nullable()->after('pending_duration_seconds');
            }
            
            if (!Schema::hasColumn('tickets', 'total_duration_seconds')) {
                $table->integer('total_duration_seconds')->nullable()->after('open_duration_seconds');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['open_duration_seconds', 'total_duration_seconds']);
        });
    }
};