<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_fo', function (Blueprint $table) {
            $table->id('ID');
            $table->string('CID', 32);
            $table->string('Provider', 32);
            $table->string('Register_Name', 32);
            $table->string('Site_ID', 8);
            $table->string('Status', 16);
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('Site_ID')
                  ->references('Site_ID')
                  ->on('table_remote')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_fo');
    }
};