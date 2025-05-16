<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_actions', function (Blueprint $table) {
            // Hapus foreign key constraint terlebih dahulu
            $table->dropForeign(['Action_By']);
            
            // Ubah tipe kolom Action_By menjadi string
            $table->string('Action_By')->change();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_actions', function (Blueprint $table) {
            // Ubah kembali ke unsignedBigInteger
            $table->unsignedBigInteger('Action_By')->change();
            
            // Tambahkan kembali foreign key
            $table->foreign('Action_By')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }
};