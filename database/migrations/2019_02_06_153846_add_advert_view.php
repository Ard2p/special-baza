<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAdvertView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('adverts', function (Blueprint $table) {
         $table->integer('views')->default(0);
         $table->integer('guest_views')->default(0);
        });

        Schema::create('advert_view_user', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
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
        Schema::table('adverts', function (Blueprint $table) {
            $table->dropColumn('views');
        });
    }
}
