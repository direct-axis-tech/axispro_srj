<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('0_employees', function (Blueprint $table) {
            $table->index('nationality');
            $table->index('bank_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('0_employees', function (Blueprint $table) {
            $table->dropIndex(['nationality']);
            $table->dropIndex(['bank_id']);
        });
    }
}
