<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeLeadFoundation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('lead_offer_positions', function (Blueprint $table){
           $table->bigIncrements('id');
           $table->unsignedBigInteger('amount');
           $table->unsignedBigInteger('lead_offer_id');
           $table->unsignedInteger('user_id');
           $table->string('worker_type');
           $table->unsignedBigInteger('worker_id');
       });

        Schema::table('lead_offer_positions', function (Blueprint $table) {
            $table->foreign('lead_offer_id')
                ->references('id')
                ->on('lead_offers')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('orders', function (Blueprint $table){
            $table->unsignedInteger('contractor_id')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {

            $table->foreign('contractor_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        Schema::dropIfExists('dispatcher_leads_orders');

        Schema::create('dispatcher_leads_orders', function (Blueprint $table){
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('lead_id');
        });


        Schema::table('dispatcher_leads_orders', function (Blueprint $table) {

            $table->foreign('lead_id')
                ->references('id')
                ->on('dispatcher_leads')
                ->onDelete('cascade');

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');
        });

        Schema::dropIfExists('vehicles_order');

        Schema::create('order_workers', function (Blueprint $table){

            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('worker_id');
            $table->string('worker_type');

            $table->unsignedBigInteger('amount')->default(0);
            $table->unsignedBigInteger('delivery_cost')->default(0);

            $table->string('order_type');
            $table->unsignedInteger('order_duration');

            $table->unsignedBigInteger('regional_representative_commission')->default(0);
            $table->unsignedInteger('regional_representative_id')->nullable();


            $table->timestamp('date_from')->nullable();
            $table->timestamp('date_to')->nullable();

        });

        Schema::table('order_workers', function (Blueprint $table) {

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');

            $table->foreign('regional_representative_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });


        \Modules\Dispatcher\Entities\Lead::query()->delete();
        \Modules\Orders\Entities\Order::query()->delete();

        Schema::table('dispatcher_orders', function (Blueprint $table){
           $table->dropForeign(['contractor_id']);
           $table->dropForeign(['customer_id']);
           $table->dropForeign(['user_id']);
        });

        Schema::table('dispatcher_contractor_pays', function (Blueprint $table){
            $table->dropForeign(['dispatcher_order_id']);
        });
        Schema::table('dispatcher_contractor_pays', function (Blueprint $table){
            $table->dropColumn(['dispatcher_order_id']);
        });

        Schema::table('dispatcher_invoices', function (Blueprint $table){
            $table->dropForeign(['dispatcher_order_id']);
        });
        Schema::table('dispatcher_invoices', function (Blueprint $table){
            $table->dropColumn(['dispatcher_order_id']);
        });



        Schema::dropIfExists('dispatcher_orders');
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
