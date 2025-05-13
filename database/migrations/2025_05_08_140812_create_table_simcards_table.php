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
        Schema::create('table_simcard', function (Blueprint $table) {
            $table->string('Sim_Number', 16)->primary();
            $table->string('Provider', 16);
            $table->string('Site_ID', 16);
            $table->text('Informasi_Tambahan')->nullable(); // Tambahkan nullable()
            $table->string('SN_Card', 16);
            $table->string('Status', 16);
            $table->timestamps();

            // Foreign key untuk Site_ID yang terhubung ke table_remote
            $table->foreign('Site_ID')
                ->references('Site_ID')
                ->on('table_remote')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_simcard');
    }
};