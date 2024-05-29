<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIndividualFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('individual_requisites', function (Blueprint $table) {

            $table->string('passport_type')->default('main')->nullable();
            $table->string('resident_card_citizen')->nullable();
            $table->string('resident_card_register_number')->nullable();
            $table->date('resident_card_date_of_issue')->nullable();
            $table->date('resident_card_valid_until')->nullable();
            $table->string('resident_card_place_of_birth')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
