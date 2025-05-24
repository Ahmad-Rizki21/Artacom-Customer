<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Add the new column, make it nullable, and place it after the 'Status' column.
            // Add an index for potentially faster lookups based on the current escalation level.
            $table->string('Current_Escalation_Level')->nullable()->after('Status')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Remove the index first if it exists
            // The index name follows Laravel's convention: table_column_index
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $doctrineTable = $sm->listTableDetails('tickets');
            if ($doctrineTable->hasIndex('tickets_current_escalation_level_index')) {
                 $table->dropIndex('tickets_current_escalation_level_index');
            }
            
            // Then drop the column
            $table->dropColumn('Current_Escalation_Level');
        });
    }
};
