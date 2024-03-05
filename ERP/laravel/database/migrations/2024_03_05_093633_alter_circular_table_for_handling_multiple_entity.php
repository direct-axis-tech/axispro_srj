<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AlterCircularTableForHandlingMultipleEntity extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $circulars = DB::table('0_circulars')->get();

        Schema::table('0_circulars', function (Blueprint $table) {
            $table->json('entity_id')->default('[]')->change();
        });

        $circulars->each(function ($circular) {
            DB::table('0_circulars')
                ->where('id', $circular->id)
                ->update(['entity_id' => json_encode(array_filter(["{$circular->entity_id}"]))]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $circulars = DB::table('0_circulars')->get();

        DB::table('0_circulars')->update(['entity_id' => 0]);

        DB::statement("ALTER TABLE `0_circulars` ALTER COLUMN `entity_id` DROP DEFAULT");

        Schema::table('0_circulars', function (Blueprint $table) {
            $table->integer('entity_id')->charset(null)->collation(null)->change();
        });

        $circulars->each(function ($circular) {
            DB::table('0_circulars')
                ->where('id', $circular->id)
                ->update(['entity_id' => Arr::first(json_decode($circular->entity_id))]);
        });
    }
}
