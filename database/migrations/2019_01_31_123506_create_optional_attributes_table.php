<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOptionalAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('optional_attributes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('type_id');
            $table->string('name');
            $table->integer('unit_id')->default(0);
            $table->integer('field_type')->default(0);
            $table->timestamps();
        });

        Schema::create('attribute_machine', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('optional_attribute_id');
            $table->integer('machinery_id');
            $table->string('value');
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
        Schema::dropIfExists('optional_attributes');
    }
}
