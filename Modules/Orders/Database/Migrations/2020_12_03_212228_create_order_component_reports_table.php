<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderComponentReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_worker_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_worker_id');
            $table->string('worker_type');
            $table->unsignedBigInteger('worker_id');
            $table->timestamps();
        });

        Schema::create('order_worker_report_timestamp', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date')->nullable();
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();
            $table->decimal('duration')->default(0);
            $table->decimal('idle_duration')->default(0);
            $table->unsignedBigInteger('cost_per_unit')->default(0);
            $table->unsignedBigInteger('order_worker_report_id');
        });

        Schema::table('order_worker_reports', function (Blueprint $table) {
            $table->foreign('order_worker_id')
                ->references('id')
                ->on('order_workers')
                ->onDelete('cascade');
        });

        Schema::table('order_worker_report_timestamp', function (Blueprint $table) {
            $table->foreign('order_worker_report_id')
                ->references('id')
                ->on('order_worker_reports')
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
        Schema::dropIfExists('order_component_reports');
    }
}
