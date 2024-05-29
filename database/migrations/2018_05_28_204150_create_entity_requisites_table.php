<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntityRequisitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entity_requisites', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->text('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('inn')->nullable();
            $table->string('kpp')->nullable();
            $table->string('ogrn')->nullable();
            $table->string('director')->nullable();
            $table->string('booker')->nullable();
            $table->string('bank')->nullable();
            $table->string('bik')->nullable();
            $table->string('ks')->nullable();
            $table->string('rs')->nullable();
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
        Schema::dropIfExists('entity_requisites');
    }
}
