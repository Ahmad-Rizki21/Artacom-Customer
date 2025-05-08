<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateKeteranganColumnInTableRemote extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('table_remote', function (Blueprint $table) {
            $table->text('Keterangan')->nullable()->change(); // Membuat kolom Keterangan nullable
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('table_remote', function (Blueprint $table) {
            $table->text('Keterangan')->nullable(false)->change(); // Mengembalikan kolom menjadi NOT NULL
        });
    }
}