<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableRemoteHistoriesTable extends Migration
{
    public function up()
    {
        Schema::create('table_remote_histories', function (Blueprint $table) {
            $table->id();
            $table->string('remote_id');
            $table->string('action');
            $table->string('status')->nullable();
            $table->text('note')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at')->nullable();

            $table->foreign('remote_id')->references('Site_ID')->on('table_remote')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('set null');

            $table->index('remote_id');
            $table->index('changed_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('table_remote_histories');
    }
}
