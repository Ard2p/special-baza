<?php

use App\User\IndividualRequisite;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPositionGenetive extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new IndividualRequisite)->getTable(), function (Blueprint $table) {
            $table->string('position_genitive')->nullable();
            $table->string('director_position_genitive')->nullable();
        });

        Schema::table((new \App\User\EntityRequisite)->getTable(), function (Blueprint $table) {
            $table->string('position_genitive')->nullable();
            $table->string('director_position_genitive')->nullable();
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
