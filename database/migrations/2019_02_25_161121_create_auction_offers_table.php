<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuctionOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auction_offers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('auction_id');
            $table->unsignedInteger('user_id');
            $table->boolean('is_win')->default(0);
            $table->bigInteger('sum');
            $table->timestamps();
        });

        Schema::table('auctions', function (Blueprint $table) {
                $table->text('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auction_offers');
    }
}
