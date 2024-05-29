<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMachineryModelsAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('machinery_models_attributes', function (Blueprint $table) {
            $table->unsignedBigInteger('machinery_model_id');
            $table->unsignedInteger('optional_attribute_id');
            $table->string('value')->nullable();

        });

        Schema::table('machinery_models_attributes', function (Blueprint $table) {

            $table->foreign('machinery_model_id')
                ->references('id')
                ->on('machinery_models')
                ->onDelete('cascade');

            $table->foreign('optional_attribute_id')
                ->references('id')
                ->on('optional_attributes')
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
        Schema::dropIfExists('machinery_models_attributes');
    }
}
