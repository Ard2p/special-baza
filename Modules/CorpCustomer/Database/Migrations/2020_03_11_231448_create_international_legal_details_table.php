<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInternationalLegalDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('international_legal_details', function (Blueprint $table) {
            $table->bigIncrements('id');
             $table->string('account_name');
             $table->string('account')->nullable();
             $table->string('swift')->nullable();
             $table->string('beneficiary_bank')->nullable();
             $table->string('code')->nullable();
             $table->string('bank_address')->nullable();
             $table->unsignedInteger('user_id');
            $table->timestamps();
        });

        Schema::table('international_legal_details', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('international_legal_details');
    }
}
