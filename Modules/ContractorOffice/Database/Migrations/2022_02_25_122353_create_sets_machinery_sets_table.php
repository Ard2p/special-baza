<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSetsMachinerySetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('machinery_sets', function (Blueprint $table) {
            $table->dropForeign(['company_branch_id']);
        });

        Schema::create('machinery_sets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->jsonb('prices');
            $table->unsignedBigInteger('company_branch_id');
            $table->timestamps();
        });

        Schema::table('machinery_sets', function (Blueprint $table) {
            $table->foreign('company_branch_id')->references('id')->on('company_branches')->onDelete('cascade');
        });

        Schema::create('machinery_set_equipment', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('model_id')->nullable();
            $table->unsignedBigInteger('machinery_set_id');
            $table->unsignedInteger('category_id');
            $table->unsignedBigInteger('count');
        });

        Schema::table('machinery_set_equipment', function (Blueprint $table) {

            $table->foreign('model_id')
                ->references('id')
                ->on('machinery_models')
                ->onDelete('set null');

            $table->foreign('category_id')
                ->references('id')
                ->on('types')
                ->onDelete('cascade');

            $table->foreign('machinery_set_id')
                ->references('id')
                ->on('machinery_sets')
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
        Schema::dropIfExists('machinery_sets');
    }
}
