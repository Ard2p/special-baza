<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsCertificatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ins_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->string('name');
            $table->double('premium');
            $table->double('sum');
            $table->foreignId('order_worker_id')->constrained();
            $table->string('attachment')->nullable();
            $table->tinyInteger('status');
            $table->foreignId('company_branch_id')->constrained();
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
        Schema::dropIfExists('ins_certificates');
    }
}
