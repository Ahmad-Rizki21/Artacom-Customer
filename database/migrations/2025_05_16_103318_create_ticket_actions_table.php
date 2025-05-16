<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_actions', function (Blueprint $table) {
            $table->id();
            $table->string('No_Ticket', 64); // Sesuaikan dengan tipe data di tickets
            $table->foreign('No_Ticket')
                  ->references('No_Ticket')
                  ->on('tickets') // Perbaiki referensi ke 'tickets'
                  ->onDelete('cascade');
            $table->text('Action_Taken')->nullable();
            $table->timestamp('Action_Time')->useCurrent();
            $table->unsignedBigInteger('Action_By');
            $table->foreign('Action_By')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            $table->string('Action_Level', 255)->nullable();
            $table->timestamps();

            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_actions');
    }
};