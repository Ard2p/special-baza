<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSoftDeletesToRequisites extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('individual_requisites', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('entity_requisites', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('individual_requisites', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('entity_requisites', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
