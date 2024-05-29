<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('alias')->unique();
            $table->string('keywords')->nullable();
            $table->string('h1')->nullable();
            $table->string('description')->nullable();
            $table->text('content');
            $table->string('image')->nullable();
            $table->boolean('is_publish')->default(1);
            $table->string('image_alt')->nullable();
            $table->string('button_text');
            $table->text('form_text');
            $table->timestamps();
        });

        Schema::create('submit_services', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->text('comment')->nullable();
            $table->integer('region_id')->default(0);
            $table->integer('city_id')->default(0);
            $table->integer('type_id')->default(0);
            $table->integer('proposal_id')->default(0);
            $table->integer('user_id')->default(0);
            $table->integer('service_id');
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
        Schema::dropIfExists('services');
    }
}
