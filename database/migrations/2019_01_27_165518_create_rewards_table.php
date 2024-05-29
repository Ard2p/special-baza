<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('adverts', function (Blueprint $table) {
            $table->integer('reward_id')->default(0);
            $table->text('reward_text')->nullable();
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
        Schema::dropIfExists('rewards');

        Schema::table('adverts', function (Blueprint $table) {
            $table->dropColumn('reward_id');
            $table->dropColumn('reward_text');
            $table->dropColumn('description');
        });
    }
}
