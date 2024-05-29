<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SetNullableRequisiteTypeId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('balance_histories', function ($table) {
            $table->integer('requisite_id')->nullable()->change();
            $table->string('requisite_type')->nullable()->change();
        });

        Schema::table('finance_transactions', function ($table) {
            $table->integer('requisites_id')->nullable()->change();
            $table->string('requisites_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
