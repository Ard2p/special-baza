<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServiceLocalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_locales', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('keywords')->nullable();
            $table->string('h1')->nullable();
            $table->string('description')->nullable();
            $table->text('content')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('button_text')->nullable();
            $table->text('form_text')->nullable();
            $table->string('locale');
            $table->string('comment_label')->nullable();
            $table->unsignedInteger('service_id');
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
        Schema::dropIfExists('service_locales');
    }
}
