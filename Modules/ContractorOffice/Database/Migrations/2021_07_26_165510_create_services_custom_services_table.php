<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServicesCustomServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('company_branch_id');
            $table->timestamps();
        });

        Schema::create('custom_services_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('category_id');
            $table->unsignedBigInteger('custom_service_id');
        });

        Schema::table('custom_services_categories', function (Blueprint $table) {

            $table->foreign('category_id')
                ->references('id')
                ->on('types')
                ->onDelete('cascade');

            $table->foreign('custom_service_id')
                ->references('id')
                ->on('custom_services')
                ->onDelete('cascade');
        });

        Schema::table('custom_services', function (Blueprint $table) {

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
        Schema::dropIfExists('services\_custom_services');
    }
}
