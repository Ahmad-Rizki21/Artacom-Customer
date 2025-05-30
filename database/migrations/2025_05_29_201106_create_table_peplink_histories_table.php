<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('table_peplink_histories', function (Blueprint $table) {
            $table->id();
            $table->string('peplink_sn', 24); // Menyimpan SN dari TablePeplink
            $table->string('action'); // Jenis aksi: created, updated, deleted
            $table->string('status')->nullable(); // Status terkait aksi
            $table->text('note')->nullable(); // Catatan atau detail tindakan
            $table->json('old_values')->nullable(); // Data sebelum perubahan (untuk update)
            $table->json('new_values')->nullable(); // Data setelah perubahan (untuk created/update)
            $table->unsignedBigInteger('changed_by')->nullable(); // User yang melakukan perubahan
            $table->timestamp('changed_at'); // Waktu perubahan
            $table->timestamps();

            $table->foreign('peplink_sn')->references('SN')->on('table_peplink')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('table_peplink_histories');
    }
};