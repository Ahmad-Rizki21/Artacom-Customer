<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('remote_atmbsi', function (Blueprint $table) {
            $table->string('Site_ID', 255)->nullable(false);
            $table->string('Site_Name', 50)->nullable(false);
            $table->string('Branch', 50)->nullable(false);
            $table->string('IP_Address', 32)->nullable(false);
            $table->string('Vlan', 25)->nullable(false);
            $table->string('Controller', 16)->nullable(false);
            $table->string('Customer', 16)->nullable(false);
            $table->date('Online_Date')->nullable(false);
            $table->string('Link', 8)->nullable(false);
            $table->string('Status', 16)->nullable(false);
            $table->text('Keterangan')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remote_atmbsi');
    }
};