<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToStockMovesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('0_stock_moves', function (Blueprint $table) {
            $table->index(['contract_id', 'type']);
            $table->index('maid_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('0_stock_moves', function (Blueprint $table) {
            $table->dropIndex(['contract_id', 'type']);
            $table->dropIndex(['maid_id']);
        });
    }
}
