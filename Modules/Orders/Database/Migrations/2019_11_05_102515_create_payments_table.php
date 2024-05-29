<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentsssTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('payments', 'alpha_payments');
        Schema::dropIfExists('payments');
         Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('system');
            $table->string('currency');
            $table->unsignedBigInteger('amount');
            $table->string('status');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');
        });
        Schema::disableForeignKeyConstraints();
        Schema::table('tinkoff_payments', function (Blueprint $table) {
            $table->renameColumn('payment_id', 'interior_payment_id');
        });
        Schema::table('tinkoff_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_id');
            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->onDelete('cascade');
        });
        Schema::enableForeignKeyConstraints();
        DB::beginTransaction();
        foreach (\App\Finance\TinkoffPayment::all() as $item){

           $payment =  \Modules\Orders\Entities\Payment::create([
               'user_id' => $item->user_id,
               'amount' => $item->amount,
               'order_id' => $item->order_id,
               'system' => 'tinkoff',
               'currency' => 'RUB',
               'status' => $item->order_id,
            ]);
           $item->update([
               'payment_id' => $payment->id
           ]);
        }

        DB::commit();

        Schema::table('tinkoff_payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['order_id']);
            $table->dropColumn(['user_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
