<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropAdvertOfferTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('user_advert_proposal');

        Schema::table('advert_offers', function (Blueprint $table) {
            $table->integer('advert_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('user_advert_proposal', function (Blueprint $table) {
            $table->integer('advert_offer_id')->default(0);
            $table->integer('advert_id')->default(0);
            $table->boolean('accept')->default(0);
            $table->timestamps();
        });
    }
}
