<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsWinToAdvertOffer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('advert_offers', function (Blueprint $table) {
           $table->boolean('is_win')->default(0);
            $table->integer('rate')->default(0);
            $table->text('feedback')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('advert_offers', function (Blueprint $table) {
            $table->dropColumn('is_win');
        });
    }
}
