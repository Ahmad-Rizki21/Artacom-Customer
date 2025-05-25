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
        Schema::create('ticket_evidences', function (Blueprint $table) {
        $table->id();
        $table->string('No_Ticket');
        $table->string('file_name');
        $table->string('file_path');
        $table->string('file_type');
        $table->string('mime_type');
        $table->unsignedBigInteger('file_size');
        $table->text('description')->nullable();
        $table->unsignedBigInteger('uploaded_by')->nullable();
        $table->string('upload_stage');
        $table->timestamps();

        $table->foreign('No_Ticket')->references('No_Ticket')->on('tickets')->onDelete('cascade');
        $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_evidences');
    }
};