<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopPartsSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_parts_sales', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('status');
            $table->string('source')->default('transbaza');
            $table->timestamp('date')->nullable();
            $table->unsignedBigInteger('parts_request_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();

            $table->unsignedInteger('creator_id')->nullable();
            $table->unsignedBigInteger('company_branch_id');

            $table->timestamps();
        });

        Schema::table('shop_parts_sales', function (Blueprint $table) {


            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('parts_request_id')
                ->references('id')
                ->on('shop_parts_requests')
                ->onDelete('set null');

            $table->foreign('customer_id')
                ->references('id')
                ->on('dispatcher_customers')
                ->onDelete('set null ');

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');
        });

        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropForeign(['posting_id']);
            $table->dropColumn('posting_id');
            $table->string('owner_type')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('company_branch_id');
        });

        Schema::table('stock_items', function (Blueprint $table) {
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
        Schema::dropIfExists('shop_parts_sales');
    }
}
