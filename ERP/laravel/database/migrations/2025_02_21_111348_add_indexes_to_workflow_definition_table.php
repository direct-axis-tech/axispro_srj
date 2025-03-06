<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToWorkflowDefinitionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('0_workflow_definitions', function (Blueprint $table) {
            $table->index(['entity_id', 'entity_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('0_workflow_definitions', function (Blueprint $table) {
            $table->dropIndex(['entity_id', 'entity_type_id']);
        });
    }
}
