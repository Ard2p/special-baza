<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMachinerySales extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('machinery_sales', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date')->nullable();

            $table->string('pay_type');
            $table->string('account_number');
            $table->date('account_date');

            $table->unsignedInteger('creator_id')->nullable();
            $table->unsignedBigInteger('company_branch_id');
            $table->timestamps();
        });

        Schema::table('machinery_sales', function (Blueprint $table) {

            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');
        });

        Schema::create('machinery_purchases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('pay_type');

            $table->string('account_number');
            $table->date('account_date');

            $table->unsignedInteger('creator_id')->nullable();
            $table->unsignedBigInteger('provider_id')->nullable();
            $table->unsignedBigInteger('company_branch_id');

            $table->timestamps();
        });

        Schema::create('machinery_shop_characteristic', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('owner_type');
            $table->unsignedInteger('machinery_id');
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('cost');
            $table->unsignedBigInteger('engine_hours')->default(0);
            $table->string('type')->nullable();

        });

        Schema::table('machinery_shop_characteristic', function (Blueprint $table) {

            $table->foreign('machinery_id')
                ->references('id')
                ->on('machineries')
                ->onDelete('cascade');
        });

        Schema::table('machinery_purchases', function (Blueprint $table) {

            $table->foreign('provider_id')
                ->references('id')
                ->on('warehouse_parts_providers')
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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('');
    }
}
