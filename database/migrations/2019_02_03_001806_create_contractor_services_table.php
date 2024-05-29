<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContractorServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contractor_services', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('service_category_id');
            $table->integer('region_id')->default(0);
            $table->integer('city_id')->default(0);
            $table->string('name');
            $table->string('photo')->nullable();
            $table->longText('text')->nullable();
            $table->string('size')->nullable();
            $table->integer('sum')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('attribute_contractor_services', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('service_optional_fields_id');
            $table->integer('contractor_service_id');
            $table->string('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contractor_services');
    }
}
