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

        \Modules\Dispatcher\Entities\DispatcherInvoice::query()->delete();

        Schema::table('dispatcher_invoices', function (Blueprint $table){
            $table->string('owner_type')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('company_branch_id');
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });

        Schema::table('dispatcher_invoices', function (Blueprint $table) {

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');
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
