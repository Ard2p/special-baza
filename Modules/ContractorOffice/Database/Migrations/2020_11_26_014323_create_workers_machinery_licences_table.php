<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkersMachineryLicencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workers_machinery_licences', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('driving_category_id');
            $table->date('date_of_issue')->nullable();
            $table->date('expired_date')->nullable();
            $table->date('experience_start')->nullable();
            $table->unsignedBigInteger('workers_driver_document_id');
        });


        Schema::table('workers_machinery_licences', function (Blueprint $table) {


            $table->foreign('driving_category_id')
                ->references('id')
                ->on('driving_categories')
                ->onDelete('cascade');

            $table->foreign('workers_driver_document_id')
                ->references('id')
                ->on('workers_driver_documents')
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
        Schema::dropIfExists('workers\_machinery_licences');
    }
}
