<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScoringsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scorings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('type');
            $table->string('inn')->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('midname')->nullable();
            $table->string('birthdate')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('issue_date')->nullable();
            $table->integer('result_code')->nullable();
            $table->string('result_message',1000)->nullable();
            $table->integer('score')->nullable();
            $table->text('description')->nullable();
            $table->json('response_json')->nullable();
            $table->unsignedBigInteger('company_branch_id');
            $table->unsignedInteger('creator_id');
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
        Schema::dropIfExists('scorings');
    }
}
