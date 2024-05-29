<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTransactionType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Illuminate\Support\Facades\DB::table('transaction_types')->insert([
            [
                'name' => 'Вознаграждение РП за выполненый заказ.',
                'transaction_type' => 'representative_commission',
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \Illuminate\Support\Facades\DB::table('transaction_types')->where('transaction_type', 'representative_commission')
        ->delete();
    }
}
