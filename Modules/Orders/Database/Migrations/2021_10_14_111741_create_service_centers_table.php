<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServiceCentersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_centers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('internal_number')->nullable();
            $table->nullableMorphs('customer');
            $table->string('name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->nullable();
            $table->date('date')->nullable();
            $table->string('type');
            $table->text('description')->nullable();
            $table->text('note')->nullable();
            $table->nullableMorphs('order');

            $table->unsignedInteger('machinery_id')->nullable();
            $table->unsignedBigInteger('company_branch_id');
            $table->timestamps();
        });

        Schema::create('service_centers_mechanics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('service_center_id');
            $table->unsignedBigInteger('company_worker_id');
        });

        Schema::table('service_centers_mechanics', function (Blueprint $table) {

            $table->foreign('service_center_id')
                ->references('id')
                ->on('service_centers')
                ->onDelete('cascade');
            $table->foreign('company_worker_id')
                ->references('id')
                ->on('company_workers')
                ->onDelete('cascade');

        });

        Schema::table('service_centers', function (Blueprint $table) {

            $table->foreign('machinery_id')
                ->references('id')
                ->on('machineries')
                ->onDelete('set null');

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
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
        Schema::dropIfExists('service\_service_centers');
    }
}
