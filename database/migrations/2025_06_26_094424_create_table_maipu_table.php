<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableMaipuTable extends Migration
{
    public function up()
    {
        Schema::create('table_maipu', function (Blueprint $table) {
            $table->string('SN', 32)->primary();
            $table->string('Model', 32);
            $table->string('Kepemilikan', 32);
            $table->date('tgl_beli');
            $table->string('garansi', 16);
            $table->string('Site_ID', 16)->nullable();
            $table->string('Status', 32);
            $table->timestamps();

            // Foreign key ke remote_atmbsi Site_ID
            $table->foreign('Site_ID')->references('Site_ID')->on('remote_atmbsi')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('table_maipu');
    }
}
