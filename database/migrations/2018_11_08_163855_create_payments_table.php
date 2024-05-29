<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentssTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payments', function (Blueprint $table){
            $table->dropColumn([
                'type',
                'sum',
                'user_id',
                'requisites_id',
                'order_id',
                'pay_time',
                'data',
            ]);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->integer('user_id', false, true);
            $table->string('order_id');
            $table->integer('finance_transaction_id');
            $table->integer('status');
            $table->longText('response')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       // Schema::dropIfExists('payments');
    }
}
