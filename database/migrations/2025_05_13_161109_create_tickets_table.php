<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            // Primary Information
            $table->string('No_Ticket', 64)->primary();
            $table->string('Customer', 100);  // Diperpanjang menjadi 100
            $table->string('Catagory', 255);  // Perbaikan typo
            $table->string('Site_ID', 8);

            // Problem Information
            $table->string('Problem', 255);
            $table->string('Reported_By')->nullable();

            // PIC Information
            $table->string('Pic', 100)->nullable();
            $table->string('Tlp_Pic', 20)->nullable();

            // Status and Level Information
            $table->string('Status')->default('OPEN');
            $table->string('Open_Level', 255)->default('Level 1');

            // User yang membuat ticket
            $table->unsignedBigInteger('Open_By');

            // Closing Information
            $table->unsignedBigInteger('Closed_By')->nullable();
            $table->timestamp('Closed_Time')->nullable();
            $table->string('Closed_Level', 255)->nullable();

            // Timestamps for tracking
            $table->timestamp('Open_Time')->useCurrent();
            $table->timestamp('Pending_Start')->nullable();
            $table->timestamp('Pending_Stop')->nullable();

            // Optional detailed information
            $table->text('Pending_Reason')->nullable();
            $table->text('Problem_Summary')->nullable();
            $table->text('Classification')->nullable();
            $table->text('Action_Summry')->nullable();  // Perbaikan typo

            // Standard timestamps
            $table->timestamps();

            // Foreign Keys
            $table->foreign('Site_ID')
                ->references('Site_ID')
                ->on('table_remote')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('Open_By')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('Closed_By')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};