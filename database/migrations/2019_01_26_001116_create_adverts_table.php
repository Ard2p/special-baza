<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdvertsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adverts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('category_id')->default(0);
            $table->integer('status')->default(0);
            $table->unsignedBigInteger('sum')->default(0);
            $table->unsignedInteger('region_id')->default(0);
            $table->unsignedInteger('city_id')->default(0);
            $table->text('address')->nullable();
            $table->string('photo')->nullable();
            $table->text('coordinates')->nullable();
            $table->timestamp('actual_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('user_advert_proposal', function (Blueprint $table) {
            $table->integer('user_id')->default(0);
            $table->integer('advert_id')->default(0);
            $table->boolean('accept')->default(0);
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
        Schema::dropIfExists('adverts');
    }
}
