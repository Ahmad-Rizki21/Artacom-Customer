<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('table_fo_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fo_id'); // Reference to ID in table_fo
            $table->string('action'); // Jenis aksi: created, updated, deleted
            $table->string('status')->nullable(); // Status terkait aksi
            $table->text('note')->nullable(); // Catatan atau detail tindakan
            $table->json('old_values')->nullable(); // Data sebelum perubahan
            $table->json('new_values')->nullable(); // Data setelah perubahan
            $table->unsignedBigInteger('changed_by')->nullable(); // User yang melakukan perubahan
            $table->timestamp('changed_at'); // Waktu perubahan
            $table->timestamps();

            $table->foreign('fo_id')->references('ID')->on('table_fo')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('table_fo_histories');
    }
};