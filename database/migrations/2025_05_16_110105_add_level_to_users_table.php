<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Periksa apakah kolom 'Level' belum ada sebelum menambahkannya
            if (!Schema::hasColumn('users', 'Level')) {
                $table->string('Level')->after('password')->nullable(); // Tambahkan nullable jika diperlukan
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hanya hapus kolom jika ada
            if (Schema::hasColumn('users', 'Level')) {
                $table->dropColumn('Level');
            }
        });
    }
};