<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderIdToLead extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('point', 'string');
        DB::getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');


        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('amount')->default(0)->change();
        });

        Schema::table('vehicles_order', function (Blueprint $table) {
            $table->unsignedBigInteger('amount')->default(0)->change();
        });

        Schema::create('dispatcher_leads_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('order_id');
            $table->timestamps();
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
