<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up()
    {
        Schema::table('table_fo', function (Blueprint $table) {
            $table->string('Provider', 255)->change(); // atau lebih sesuai kebutuhan
        });
    }

    public function down()
    {
        Schema::table('table_fo', function (Blueprint $table) {
            $table->string('Provider', 32)->change();
        });
    }
};
