<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCircularAcknowledgementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('0_circular_acknowledgement_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->smallInteger('circular_id');
            $table->integer('acknowledged_by');
            $table->timestamps();

            $table->index('circular_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('0_circular_acknowledgement_details');
    }
}
