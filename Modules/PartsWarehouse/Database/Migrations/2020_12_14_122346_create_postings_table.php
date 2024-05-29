<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('warehouse_postings', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('parts_provider_id')->nullable();
            $table->string('pay_type');
            $table->date('date');
            $table->string('account_number');
            $table->date('account_date');
            $table->unsignedBigInteger('company_branch_id');
            $table->timestamps();
        });

        Schema::table('warehouse_postings', function (Blueprint $table) {

            $table->foreign('parts_provider_id')
                ->references('id')
                ->on('warehouse_parts_providers')
                ->onDelete('set null');

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');
        });


        Schema::table('stock_items', function (Blueprint $table) {
            $table->unsignedBigInteger('posting_id')->nullable();
            $table->unsignedBigInteger('cost_per_unit')->nullable();
        });

        Schema::table('stock_items', function (Blueprint $table) {

            $table->foreign('posting_id')
                ->references('id')
                ->on('warehouse_postings')
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
        Schema::dropIfExists('postings');
    }
}
