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
        Schema::create('table_peplink', function (Blueprint $table) {
            $table->string('SN', 24);
            $table->string('Model', 32);
            $table->string('Kepemilikan', 32);
            $table->date('tgl_beli');
            $table->string('garansi', 16);
            $table->string('Site_ID', 16);
            $table->string('Status', 32);
            $table->timestamps();

            // Set SN as primary key
            $table->primary('SN');

            // Foreign key site_id
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
        Schema::dropIfExists('table_peplink');
    }
};