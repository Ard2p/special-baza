<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('machine_holds');
        Schema::dropIfExists('proposal_holds');


        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('status');
            $table->unsignedInteger('amount');
            $table->unsignedInteger('region_id')->nullable();
            $table->unsignedInteger('city_id')->nullable();
            $table->string('address');
            $table->timestamp('date_from')->nullable();
            $table->timestamp('date_to')->nullable();
            $table->point('coordinates')->nullable();
            $table->unsignedInteger('shifts_count')->default(0);
            $table->unsignedInteger('system_commission')->default(0);
            $table->unsignedInteger('regional_representative_commission')->default(0);
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('regional_representative_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('vehicles_order', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('machinery_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedInteger('amount');
            $table->timestamp('date_from')->nullable();
            $table->timestamp('date_to')->nullable();
            $table->timestamps();
        });
        Schema::table('tinkoff_payments', function (Blueprint $table) {

            $table->dropColumn('proposal_hold_id');
          $table->unsignedBigInteger('order_id');

        });


        Schema::table('tinkoff_payments', function (Blueprint $table) {
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('vehicles_order', function (Blueprint $table) {
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');
        });

        Schema::create('orders_need_type', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('type_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedInteger('brand_id')->nullable();
            $table->text('comment')->nullable();
        });

        Schema::table('orders_need_type', function (Blueprint $table) {
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');
        });

        Schema::table('free_days', function (Blueprint $table) {
            $table->dropColumn('proposal_hold_id');
            $table->unsignedBigInteger('order_id')->nullable();
        });

        Schema::table('free_days', function (Blueprint $table) {
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
        Schema::dropIfExists('orders');
    }
}
