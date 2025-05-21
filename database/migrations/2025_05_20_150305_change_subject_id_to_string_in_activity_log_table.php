<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeSubjectIdToStringInActivityLogTable extends Migration
{
    public function up()
    {
        // Step 1: Update existing subject_id values to ensure compatibility
        DB::table('activity_log')
            ->whereNull('subject_id')
            ->update(['subject_id' => '0']); // Set NULL values to '0' (or another default)

        // Step 2: Change the column type to VARCHAR
        Schema::table('activity_log', function (Blueprint $table) {
            $table->string('subject_id')->nullable()->change(); // Temporarily allow NULL
        });

        // Step 3: (Optional) Set NOT NULL constraint if needed
        Schema::table('activity_log', function (Blueprint $table) {
            $table->string('subject_id')->nullable(false)->change();
        });
    }

    public function down()
    {
        // Revert to unsignedBigInteger
        Schema::table('activity_log', function (Blueprint $table) {
            $table->unsignedBigInteger('subject_id')->nullable()->change();
        });
    }
}
