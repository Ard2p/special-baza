<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStaticLocalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('static_locales', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('keywords');
            $table->text('description');
            $table->string('h1');
            $table->string('image_alt')->nullable();
            $table->longText('content');
            $table->string('locale');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('static_content_id');
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
        Schema::dropIfExists('static_locales');
    }
}
