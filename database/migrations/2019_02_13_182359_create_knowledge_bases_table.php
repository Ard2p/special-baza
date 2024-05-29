<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKnowledgeBasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('system_function_id')->default(0);
            $table->integer('system_module_id')->default(0);
            $table->string('title')->nullable();
            $table->string('h1')->nullable();
            $table->text('keywords')->nullable();
            $table->text('description')->nullable();
            $table->longText('content');
            $table->string('image')->nullable();
            $table->boolean('is_publish')->default(1);
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
        Schema::dropIfExists('knowledge_bases');
    }
}
