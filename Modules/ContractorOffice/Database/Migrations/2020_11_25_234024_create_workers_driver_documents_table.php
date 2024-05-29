<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkersDriverDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('company_workers', function (Blueprint $table) {

            $table->string('passport_number')->nullable();
            $table->jsonb('passport_scans')->nullable();
            $table->string('passport_place_of_issue')->nullable();
            $table->string('passport_date_of_issue')->nullable();

        });

        Schema::create('workers_driver_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('driving_licence_number')->nullable();
            $table->date('driving_licence_expired_date')->nullable();
            $table->string('driving_licence_place_of_issue')->nullable();
            $table->jsonb('driving_licence_scans')->nullable();


            $table->string('machinery_licence_number')->nullable();
            $table->jsonb('machinery_licence_scans')->nullable();
            $table->string('machinery_licence_place_of_issue')->nullable();
            $table->string('machinery_licence_date_of_issue')->nullable();

            $table->unsignedBigInteger('company_worker_id');

            $table->timestamps();
        });

        Schema::table('workers_driver_documents', function (Blueprint $table) {

            $table->foreign('company_worker_id')
                ->references('id')
                ->on('company_workers')
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
        Schema::dropIfExists('workers\_driver_documents');
    }
}
