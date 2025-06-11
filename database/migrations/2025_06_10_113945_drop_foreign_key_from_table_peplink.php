<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropForeignKeyFromTablePeplink extends Migration
{
    public function up()
    {
        Schema::table('table_peplink', function (Blueprint $table) {
            $table->dropForeign(['Site_ID']);
        });
    }

    public function down()
    {
        Schema::table('table_peplink', function (Blueprint $table) {
            $table->foreign('Site_ID')->references('Site_ID')->on('table_remote')->onDelete('cascade')->onUpdate('cascade');
        });
    }
}