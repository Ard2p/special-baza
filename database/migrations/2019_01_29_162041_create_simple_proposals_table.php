<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSimpleProposalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('simple_proposals', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('button_text');
            $table->text('form_text');
            $table->text('url');
            $table->boolean('include_sub')->default(0);
            $table->boolean('default')->default(0);
            $table->string('position')->default('bottom');
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::table('simple_proposals')->insert([
            'name' => 'Стандартная форма',
            'button_text' => 'Создать заказ',
            'form_text' => 'Быстрый заказ!',
            'url' => '*',
            'include_sub' => 0,
            'default' => 1,
            'position' => 'default',
        ]);

        Schema::create('submit_simple_proposals', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->string('phone');
            $table->text('comment')->nullable();
            $table->integer('simple_proposal_id');
            $table->integer('region_id')->default(0);
            $table->integer('city_id')->default(0);
            $table->integer('type_id')->default(0);
            $table->integer('proposal_id')->default(0);
            $table->integer('user_id')->default(0);
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
        Schema::dropIfExists('simple_proposals');
        Schema::dropIfExists('submit_simple_proposals');
    }
}
