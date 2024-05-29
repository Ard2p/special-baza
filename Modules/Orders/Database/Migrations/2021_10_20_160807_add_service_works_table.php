<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddServiceWorksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_works', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('company_branch_id');
        });

        Schema::table('service_works', function (Blueprint $table) {

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');
        });

        Schema::create('service_works_center', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('service_work_id');
            $table->unsignedBigInteger('service_center_id');
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('count');
        });

        Schema::table('service_works_center', function (Blueprint $table) {

            $table->foreign('service_work_id')
                ->references('id')
                ->on('service_works')
                ->onDelete('cascade');

            $table->foreign('service_center_id')
                ->references('id')
                ->on('service_centers')
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
        Schema::table('', function (Blueprint $table) {

        });
    }
}
