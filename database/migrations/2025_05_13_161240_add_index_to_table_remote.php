<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan tipe data Customer sama di kedua tabel
        Schema::table('table_remote', function (Blueprint $table) {
            $table->string('Customer', 16)->change();
            $table->index('Customer');
        });
    }

    public function down(): void
    {
        Schema::table('table_remote', function (Blueprint $table) {
            $table->dropIndex(['Customer']);
        });
    }
};