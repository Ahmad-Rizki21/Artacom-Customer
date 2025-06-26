<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableMaipuHistoriesTable extends Migration
{
    public function up()
    {
        Schema::create('table_maipu_histories', function (Blueprint $table) {
            $table->id();
            $table->string('maipu_sn', 32);
            $table->string('action'); // created, updated, deleted
            $table->string('status')->nullable();
            $table->text('note')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('table_maipu_histories');
    }
}
