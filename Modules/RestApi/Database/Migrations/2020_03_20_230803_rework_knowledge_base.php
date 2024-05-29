<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ReworkKnowledgeBase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('system_modules');
        Schema::dropIfExists('system_functions');
        Schema::dropIfExists('knowledge_base_locales');
        Schema::dropIfExists('knowledge_bases');


        Schema::create('knowledge_base_faq', function (Blueprint $table){

            $table->increments('id');
            $table->string('name');
            $table->longText('content');
            $table->unsignedInteger('category_id');
            $table->timestamps();

        });

        Schema::create('knowledge_base_faq_roles', function (Blueprint $table){
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('knowledge_base_faq_id');
        });

        Schema::table('knowledge_base_faq_roles', function (Blueprint $table) {
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

            $table->foreign('knowledge_base_faq_id')
                ->references('id')
                ->on('knowledge_base_faq')
                ->onDelete('cascade');
        });

        Schema::create('knowledge_base_categories', function (Blueprint $table){

            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('knowledge_base_role_id')->nullable();
            $table->unsignedInteger('domain_id');

        });

        Schema::create('knowledge_base_roles', function (Blueprint $table){

            $table->increments('id');
            $table->string('name');

        });


        Schema::table('knowledge_base_categories', function (Blueprint $table) {
            $table->foreign('domain_id')
                ->references('id')
                ->on('domains')
                ->onDelete('cascade');

            $table->foreign('knowledge_base_role_id')
                ->references('id')
                ->on('knowledge_base_roles')
                ->onDelete('set null');
        });


        Schema::table('knowledge_base_faq', function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('id')
                ->on('knowledge_base_categories')
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
        //
    }
}
