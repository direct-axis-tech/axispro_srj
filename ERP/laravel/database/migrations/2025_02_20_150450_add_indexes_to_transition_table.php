<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToTransitionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('0_task_transitions', function (Blueprint $table) {
            $table->index('task_id');
            $table->index('completed_by');
            $table->index(['assigned_entity_id', 'assigned_entity_type_id'], 'task_transitions_assigned_entity_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('0_task_transitions', function (Blueprint $table) {
            $table->dropIndex(['task_id']);
            $table->dropIndex(['completed_by']);
            $table->dropIndex('task_transitions_assigned_entity_idx');
        });
    }
}
