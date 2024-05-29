<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeInternationalDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('international_legal_details', function (Blueprint $table) {

            $table->string('legal_address')->nullable();
            $table->string('actual_address')->nullable();
            $table->string('signatory_name')->nullable();
            $table->string('signatory_position')->nullable();
        });

        Schema::table('dispatcher_customers', function (Blueprint $table) {
            $table->string('contact_position')->nullable();
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
