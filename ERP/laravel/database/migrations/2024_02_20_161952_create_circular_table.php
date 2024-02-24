<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCircularTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('0_circulars', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('reference');
            $table->smallInteger('entity_type_id');
            $table->integer('entity_id');
            $table->string('memo');
            $table->string('file');
            $table->date('circular_date');
            $table->boolean('inactive')->default(0);
            $table->smallInteger('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('0_circulars');
    }
}
