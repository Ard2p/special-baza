<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePartsProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('warehouse_parts_providers', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('company_name');
            $table->string('address')->nullable();

            $table->unsignedInteger('city_id')->nullable();
            $table->unsignedInteger('region_id')->nullable();

            $table->unsignedInteger('creator_id')->nullable();
            $table->unsignedBigInteger('company_branch_id');

            $table->timestamps();
        });

        Schema::table('warehouse_parts_providers', function (Blueprint $table){

            $table->foreign('city_id')
                ->references('id')
                ->on('cities')
                ->onDelete('set null');

            $table->foreign('region_id')
                ->references('id')
                ->on('regions')
                ->onDelete('set null');

            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

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
        Schema::dropIfExists('parts_providers');
    }
}
