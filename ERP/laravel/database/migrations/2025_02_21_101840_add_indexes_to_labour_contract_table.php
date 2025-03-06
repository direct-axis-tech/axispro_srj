<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToLabourContractTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('0_labour_contracts', function (Blueprint $table) {
            $table->index(['type', 'contract_no']);
            $table->index('reference');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('0_labour_contracts', function (Blueprint $table) {
            $table->dropIndex(['type', 'contract_no']);
            $table->dropIndex(['reference']);
            $table->dropIndex(['created_by']);
        });
    }
}
