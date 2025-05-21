<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeSubjectIdNullableInActivityLogTable extends Migration
{
    public function up()
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->string('subject_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->string('subject_id')->nullable(false)->change();
        });
    }
}