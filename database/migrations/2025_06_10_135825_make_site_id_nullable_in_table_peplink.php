<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeSiteIdNullableInTablePeplink extends Migration
{
    public function up()
    {
        Schema::table('table_peplink', function (Blueprint $table) {
            $table->string('Site_ID', 8)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('table_peplink', function (Blueprint $table) {
            $table->string('Site_ID', 8)->nullable(false)->change();
        });
    }
}