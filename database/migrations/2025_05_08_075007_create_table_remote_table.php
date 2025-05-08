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
        Schema::create('table_remote', function (Blueprint $table) {
            $table->string('Site_ID')->primary();
            $table->string('Nama_Toko', 32);
            $table->string('DC', 32);
            $table->string('IP_Address', 32);
            $table->string('Vlan', 4);
            $table->string('Controller', 16);
            $table->string('Customer', 16);
            $table->date('Online_Date');
            $table->string('Link', 8);
            $table->string('Status', 16);
            $table->text('Keterangan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_remote');
    }
};