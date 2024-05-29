<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKnowledgeBaseLocalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('knowledge_base_locales', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('h1')->nullable();
            $table->text('keywords')->nullable();
            $table->text('description')->nullable();
            $table->longText('content');
            $table->string('locale');
            $table->unsignedInteger('knowledge_base_id');
            $table->timestamps();
        });



        Schema::table('knowledge_base_locales', function (Blueprint $table) {
            $table->foreign('knowledge_base_id')->references('id')
                ->on('knowledge_bases')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('knowledge_base_locales');
    }
}
