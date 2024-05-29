<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddModelsFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('machinery_models', function (Blueprint $table) {
            $table->unsignedBigInteger('insurance_without_collateral')->default(0);
            $table->unsignedBigInteger('insurance_service')->default(0);
            $table->unsignedBigInteger('insurance_overdue')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
}
