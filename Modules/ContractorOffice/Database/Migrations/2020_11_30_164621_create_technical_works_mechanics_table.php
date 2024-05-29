<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTechnicalWorksMechanicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('technical_works_mechanics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('technical_work_id');
            $table->unsignedBigInteger('company_worker_id');
        });

        Schema::table('technical_works_mechanics', function (Blueprint $table) {

            $table->foreign('company_worker_id')
                ->references('id')
                ->on('company_workers')
                ->onDelete('cascade');

            $table->foreign('technical_work_id')
                ->references('id')
                ->on('machinery_technical_works')
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
        Schema::dropIfExists('technical_works_mechanics');
    }
}
