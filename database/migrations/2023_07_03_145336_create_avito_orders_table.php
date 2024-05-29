<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvitoOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('avito_orders', function (Blueprint $table) {
            $table->id();
            $table->string('avito_ad_id');
            $table->string('avito_order_id');
            $table->foreignId('order_id')->nullable()->constrained();
            $table->foreignId('company_branch_id')->nullable()->constrained();
            $table->string('coordinate_x')->nullable();
            $table->string('coordinate_y')->nullable();
            $table->string('rent_address')->nullable();
            $table->timestamp('start_date_from')->nullable();
            $table->timestamp('start_date_to')->nullable();
            $table->integer('rental_duration')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('dispatcher_customers');
            $table->unsignedInteger('contact_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('inn')->nullable();
            $table->tinyInteger('status');
            $table->timestamps();

            $table->foreign('contact_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('avito_orders');
    }
}
