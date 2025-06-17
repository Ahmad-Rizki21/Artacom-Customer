<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifySnCardLengthOnTableSimcard extends Migration
{
    public function up()
    {
        Schema::table('table_simcard', function (Blueprint $table) {
            $table->string('SN_Card', 55)->change();
        });
    }

    public function down()
    {
        Schema::table('table_simcard', function (Blueprint $table) {
            $table->string('SN_Card', 16)->change();
        });
    }
}
