<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeBalanceColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('balance');
            $table->integer('current_role');
        });
        Schema::table('documents', function (Blueprint $table) {
            $table->integer('proposal_id')->nullable();
        });
        Schema::table('individual_requisites', function (Blueprint $table) {
            $table->integer('active')->default(1);
        });
        Schema::table('entity_requisites', function (Blueprint $table) {
            $table->integer('active')->default(1);
        });
        Schema::table('balance_histories', function (Blueprint $table) {
            $table->integer('requisite_id');
            $table->string('requisite_type');
            $table->string('billing_type');
        });
        Schema::table('proposals', function (Blueprint $table) {
            $table->timestamp('end_date')->nullable();
        });
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->integer('balance_type');
            $table->integer('requisites_id');
            $table->string('requisites_type');
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
