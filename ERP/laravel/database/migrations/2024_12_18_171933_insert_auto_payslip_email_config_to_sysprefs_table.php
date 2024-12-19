<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertAutoPayslipEmailConfigToSysprefsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('0_sys_prefs')->insert([
            [
                "name" => "auto_payslip_email",
                "category" => "setup.hr",
                "type" => "bool",
                "length" => 1,
                "value" => 0
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
        DB::table('0_sys_prefs')
            ->where('name', 'auto_payslip_email')
            ->delete();
    }
}
