<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifySiteIdNullableOnTableSimcard extends Migration
{
    public function up()
    {
        Schema::table('table_simcard', function (Blueprint $table) {
            // Hapus foreign key constraint pada kolom Site_ID
            $table->dropForeign(['Site_ID']);

            // Ubah kolom Site_ID menjadi nullable
            $table->string('Site_ID', 16)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('table_simcard', function (Blueprint $table) {
            // Kembalikan kolom Site_ID menjadi NOT NULL
            $table->string('Site_ID', 16)->nullable(false)->change();

            // Tambahkan foreign key constraint kembali
            $table->foreign('Site_ID')->references('Site_ID')->on('table_remote')->onDelete('cascade');
        });
    }
}
