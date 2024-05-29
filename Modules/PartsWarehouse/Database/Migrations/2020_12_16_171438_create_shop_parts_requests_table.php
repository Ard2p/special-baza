<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopPartsRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_parts_requests', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->timestamp('date')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('pay_type')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->unsignedBigInteger('company_branch_id');

            $table->timestamps();
        });

        Schema::table('shop_parts_requests', function (Blueprint $table) {

            $table->foreign('customer_id')
                ->references('id')
                ->on('dispatcher_customers')
                ->onDelete('set null');

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');
        });

        Schema::create('shop_parts_requests_positions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('parts_request_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('cost_per_unit');
        });

        Schema::table('shop_parts_requests_positions', function (Blueprint $table) {

            $table->foreign('parts_request_id')
                ->references('id')
                ->on('shop_parts_requests')
                ->onDelete('cascade');

            $table->foreign('item_id')
                ->references('id')
                ->on('stock_items')
                ->onDelete('cascade');

        });

        Schema::table('international_legal_details', function (Blueprint $table) {

            $table->unsignedBigInteger('company_branch_id')->nullable();

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
        Schema::dropIfExists('shop\_parts\_parts_requests');
    }
}
