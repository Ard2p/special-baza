<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequestDeleteServicePhonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_delete_service_phones', function (Blueprint $table) {
            $table->increments('id');
            $table->string('phone');
            $table->string('name');
            $table->string('url');
            $table->integer('region_id');
            $table->integer('city_id');
            $table->text('comment')->nullable();
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
        Schema::dropIfExists('request_delete_service_phones');
    }
}
