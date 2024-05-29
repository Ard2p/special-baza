<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServiceCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('name_style')->nullable();
            $table->string('alias')->nullable();
            $table->timestamps();
        });

        Schema::create('service_optional_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('service_category_id');
            $table->string('name');
            $table->integer('unit_id')->default(0);
            $table->integer('field_type')->default(0);
            $table->timestamps();
        });

    /*    Schema::create('optional_field_service', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('service_optional_field_id');
            $table->integer('service_category_id');
            $table->string('value');
            $table->timestamps();
        });*/

        Schema::create('units', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
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
        Schema::dropIfExists('service_categories');
    }
}
