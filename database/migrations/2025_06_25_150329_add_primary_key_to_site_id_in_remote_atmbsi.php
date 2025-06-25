<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrimaryKeyToSiteIdInRemoteAtmbsi extends Migration
{
    public function up()
    {
        Schema::table('remote_atmbsi', function (Blueprint $table) {
            // Pastikan Site_ID ada dan jadikan primary key
            $table->string('Site_ID')->change()->primary()->unique();
        });
    }

    public function down()
    {
        Schema::table('remote_atmbsi', function (Blueprint $table) {
            $table->dropPrimary('Site_ID');
            $table->string('Site_ID')->change(); // Kembalikan ke kondisi awal jika perlu
        });
    }
}