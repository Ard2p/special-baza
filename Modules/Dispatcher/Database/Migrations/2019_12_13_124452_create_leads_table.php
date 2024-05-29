<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dispatcher_leads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('customer_name');
            $table->string('phone');
            $table->string('address');
            $table->text('comment');
            $table->string('status');
            $table->timestamp('start_date')->nullable();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('city_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('dispatcher_leads_vehicles', function (Blueprint $table) {

            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->string('vehicle_type');

            $table->enum('order_type', ['hour', 'shift'])->default('shift');
            $table->unsignedInteger('order_duration')->default(0);
            $table->timestamps();
        });

        Schema::create('dispatcher_leads_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('lead_id');
            $table->unsignedInteger('type_id');
            $table->enum('order_type', ['hour', 'shift'])->default('shift');
            $table->unsignedInteger('order_duration')->default(0);
        });

        Schema::table('dispatcher_leads_categories', function (Blueprint $table) {
            $table->foreign('lead_id')
                ->references('id')
                ->on('dispatcher_leads')
                ->onDelete('cascade');
            $table->foreign('type_id')
                ->references('id')
                ->on('types')
                ->onDelete('cascade');
        });

        Schema::table('dispatcher_leads_vehicles', function (Blueprint $table) {
            $table->foreign('lead_id')
                ->references('id')
                ->on('dispatcher_leads')
                ->onDelete('cascade');

        });
        Schema::table('dispatcher_leads', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('leads');
    }
}
