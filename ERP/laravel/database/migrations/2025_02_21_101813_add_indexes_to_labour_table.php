<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToLabourTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('0_labours', function (Blueprint $table) {
            $table->index('agent_id');
            $table->index('bank_id');
            $table->index('nationality');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('0_labours', function (Blueprint $table) {
            $table->dropIndex(['agent_id']);
            $table->dropIndex(['bank_id']);
            $table->dropIndex(['nationality']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
        });
    }
}
