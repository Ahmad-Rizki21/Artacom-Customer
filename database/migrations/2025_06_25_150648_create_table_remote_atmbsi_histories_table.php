<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableRemoteAtmbsiHistoriesTable extends Migration
{
    public function up()
    {
        Schema::create('table_remote_atmbsi_histories', function (Blueprint $table) {
            $table->id();
            $table->string('remote_id'); // Foreign key ke Site_ID dari RemoteAtmbsi
            $table->string('action');    // created, updated, deleted
            $table->string('status')->nullable();
            $table->text('note')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at')->nullable();

            $table->index('remote_id'); // Tambahkan indeks
            $table->index('changed_by'); // Tambahkan indeks

            $table->foreign('remote_id')->references('Site_ID')->on('remote_atmbsi')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('table_remote_atmbsi_histories');
    }
}