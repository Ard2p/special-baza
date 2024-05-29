<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHoldPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hold_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('order_id');
            $table->unsignedInteger('user_id');
            $table->string('proposal_id')->default(0);
            $table->integer('finance_transaction_id');
            $table->integer('status');
            $table->longText('response')->nullable();
            $table->longText('request_params')->nullable();
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
        Schema::dropIfExists('hold_payments');
    }
}
