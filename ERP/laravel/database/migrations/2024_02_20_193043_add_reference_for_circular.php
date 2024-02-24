<?php

use App\Models\Hr\Circular;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReferenceForCircular extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('0_reflines')->insert([
            [
                "trans_type" => Circular::CIRCULAR,
                "prefix" => '',
                "pattern" => 'CIR{YY}{0001}',
                "description" => '',
                "default" => 1,
                "inactive" => 0
            ]
        ]);

        DB::table('0_meta_transactions')->insert([
            [
                'id' => Circular::CIRCULAR,
                'name' => 'Circular',
                'table' => '0_circulars',
                'col_type' => '',
                'col_trans_no' => 'id',
                'col_reference' => 'reference',
                'col_trans_date' => 'circular_date',
                'next_trans_no' => 0
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('0_reflines')->whereIn('trans_type', [Circular::CIRCULAR])->delete();
        DB::table('0_meta_transactions')->whereIn('id', [Circular::CIRCULAR])->delete();
    }
}
