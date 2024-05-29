<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCurrencyToPurchase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {


        Schema::table('machinery_sales', function (Blueprint $table) {
            $table->string('currency');
        });

        Schema::table('machinery_purchases', function (Blueprint $table) {
            $table->string('currency');
        });


        Schema::table('machineries', function (Blueprint $table) {
            $table->boolean('read_only')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
}
