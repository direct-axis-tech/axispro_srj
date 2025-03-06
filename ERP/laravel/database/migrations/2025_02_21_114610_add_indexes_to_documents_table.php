<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('0_documents', function (Blueprint $table) {
            $table->index('document_type');
            $table->index(['entity_id', 'entity_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('0_documents', function (Blueprint $table) {
            $table->dropIndex(['document_type']);
            $table->dropIndex(['entity_id', 'entity_type']);
        });
    }
}
