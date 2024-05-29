<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserPredictedCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_predicted_categories', function (Blueprint $table) {
             $table->unsignedInteger('user_id');
             $table->unsignedInteger('category_id');
             $table->unsignedInteger('count')->default(1);
        });

        Schema::table('user_predicted_categories', function (Blueprint $table) {
              $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('category_id')
                ->references('id')
                ->on('types')
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
        Schema::dropIfExists('user_predicted_categories');
    }
}
