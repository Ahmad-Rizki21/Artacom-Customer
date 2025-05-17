<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActionDescriptionToTicketActions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_actions', function (Blueprint $table) {
            $table->text('Action_Description')->nullable()->after('Action_Level');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_actions', function (Blueprint $table) {
            $table->dropColumn('Action_Description');
        });
    }
}