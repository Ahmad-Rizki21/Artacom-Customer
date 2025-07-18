<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('table_maipu', function (Blueprint $table) {
            $table->text('Deskripsi')->nullable()->after('Status');
        });
    }

    public function down()
    {
        Schema::table('table_maipu', function (Blueprint $table) {
            $table->dropColumn('Deskripsi');
        });
    }
};
