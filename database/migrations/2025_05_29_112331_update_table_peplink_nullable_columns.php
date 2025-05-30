<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('table_peplink', function (Blueprint $table) {
            $table->date('tgl_beli')->nullable()->change();
            $table->string('garansi', 16)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('table_peplink', function (Blueprint $table) {
            $table->date('tgl_beli')->nullable(false)->change();
            $table->string('garansi', 16)->nullable(false)->change();
        });
    }
};