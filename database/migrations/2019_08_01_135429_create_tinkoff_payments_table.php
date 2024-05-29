<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTinkoffPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tinkoff_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('payment_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('amount');
            $table->unsignedInteger('proposal_hold_id');
            $table->string('status')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();
        });

        Schema::table('tinkoff_payments', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::create('machine_holds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('machinery_id');
            $table->unsignedInteger('proposal_hold_id');
            $table->unsignedInteger('amount');
            $table->timestamp('date_from');
            $table->timestamp('date_to');
            $table->timestamps();
        });


        Schema::table('proposals', function (Blueprint $table) {
            $table->point('coordinates')->nullable();
        });

        Schema::create('proposal_holds', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('amount');
            $table->unsignedInteger('region_id')->nullable();
            $table->unsignedInteger('city_id')->nullable();
            $table->unsignedInteger('proposal_id')->nullable();
            $table->string('address');
            $table->timestamp('date_from');
            $table->timestamp('date_to');
            $table->point('coordinates')->nullable();
            $table->timestamps();
        });


        Schema::table('machine_holds', function (Blueprint $table) {
            $table->foreign('proposal_hold_id')
                ->references('id')
                ->on('proposal_holds')
                ->onDelete('cascade');
        });

        Schema::table('proposal_holds', function (Blueprint $table) {
            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
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
        Schema::dropIfExists('tinkoff_payments');
    }
}
