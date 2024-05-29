<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDirectoriesVehiclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dispatcher_vehicles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->unsignedInteger('type_id')->nullable();
            $table->unsignedInteger('city_id')->nullable();
            $table->unsignedInteger('brand_id')->nullable();
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('contractor_id');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('dispatcher_vehicles', function (Blueprint $table) {
            $table->foreign('contractor_id')
                ->references('id')
                ->on('dispatcher_contractors')
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
        Schema::dropIfExists('directories\_vehicles');
    }
}
