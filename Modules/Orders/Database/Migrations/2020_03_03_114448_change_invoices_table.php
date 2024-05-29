<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_pays', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        Schema::table('invoice_pays', function (Blueprint $table) {
            $table->enum('type', ['card', 'cashless'])->default('card');
            $table->dropForeign(['invoice_id']);
            $table->string('invoice_type')->default(\Modules\Orders\Entities\Payments\Invoice::class);
        });

        Schema::table('dispatcher_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('amount')->default(0);
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
