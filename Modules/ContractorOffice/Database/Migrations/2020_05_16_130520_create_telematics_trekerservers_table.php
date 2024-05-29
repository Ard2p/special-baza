<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTelematicsTrekerserversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('machineries', function (Blueprint $table){
            $table->unsignedBigInteger('telematics_id')->nullable();
        });

        Schema::create('telematics_trekerserver', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->point('coordinates')->nullable();
            $table->string('last_position')->nullable();
            $table->timestamps();
        });

        $machines = \App\Machinery::query()->whereHas('wialon_telematic')->get();
        foreach ($machines as $machine) {
            $machine->telematics()->associate($machine->wialon_telematic);
            $machine->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('telematics\_trekerservers');
    }
}
