<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('table_simcard_histories', function (Blueprint $table) {
            $table->id();
            $table->string('sim_number', 16); // Menyimpan Sim_Number dari TableSimcard
            $table->string('action'); // Jenis aksi: created, updated, deleted
            $table->string('status')->nullable(); // Status terkait aksi
            $table->text('note')->nullable(); // Catatan atau detail tindakan
            $table->json('old_values')->nullable(); // Data sebelum perubahan (untuk update)
            $table->json('new_values')->nullable(); // Data setelah perubahan (untuk created/update)
            $table->unsignedBigInteger('changed_by')->nullable(); // User yang melakukan perubahan
            $table->timestamp('changed_at'); // Waktu perubahan
            $table->timestamps();

            $table->foreign('sim_number')->references('Sim_Number')->on('table_simcard')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('table_simcard_histories');
    }
};