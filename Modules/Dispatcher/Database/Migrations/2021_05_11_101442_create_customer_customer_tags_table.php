<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerCustomerTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->unsignedBigInteger('company_branch_id');
        });

        Schema::table('company_tags', function (Blueprint $table) {

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');

        });

        Schema::create('company_taggables', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('company_tag_id');
            $table->unsignedBigInteger('taggable_id');
            $table->string('taggable_type');
        });

        Schema::table('company_taggables', function (Blueprint $table) {

            $table->foreign('company_tag_id')
                ->references('id')
                ->on('company_tags')
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
        Schema::dropIfExists('customer\_customer_tags');
    }
}
