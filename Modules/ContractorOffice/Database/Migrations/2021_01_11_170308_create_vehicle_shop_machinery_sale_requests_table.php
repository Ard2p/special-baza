<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVehicleShopMachinerySaleRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('machinery_sale_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamp('date')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('pay_type')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->unsignedBigInteger('company_branch_id');

            $table->string('status')->default('open');
            $table->string('reject_type')->nullable();
            $table->unsignedInteger('creator_id')->nullable();
            $table->timestamps();
        });
        Schema::table('machinery_sale_requests', function (Blueprint $table) {

            $table->foreign('customer_id')
                ->references('id')
                ->on('dispatcher_customers')
                ->onDelete('set null');

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');

            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });


        Schema::create('machinery_sale_requests_positions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('machinery_sale_request_id');
            $table->unsignedInteger('category_id');
            $table->unsignedBigInteger('model_id')->nullable();
            $table->unsignedInteger('brand_id')->nullable();

            $table->string('year')->nullable();
            $table->unsignedBigInteger('engine_hours')->nullable();
            $table->text('comment')->nullable();

            $table->unsignedInteger('amount');

        });

        Schema::table('machinery_sale_requests_positions', function (Blueprint $table) {

            $table->foreign('machinery_sale_request_id', 'sale_request_position_foreign')
                ->references('id')
                ->on('machinery_sale_requests')
                ->onDelete('cascade');

        });

        Schema::table('machinery_sales', function (Blueprint $table) {

            $table->unsignedBigInteger('machinery_sale_request_id')->nullable();
            $table->foreign('machinery_sale_request_id')
                ->references('id')
                ->on('machinery_sale_requests')
                ->onDelete('set null');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('machinery_sale_requests');
    }
}
