<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBusyTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('busy_types', function (Blueprint $table) {
            $table->string('key');
        });

        DB::table('busy_types')->insert([
            [
                'key' => \App\Machines\BusyType::TYPE_MAINTENANCE
            ],
            [
                'key' => \App\Machines\BusyType::TYPE_REPAIR
            ],
        ]);

        Schema::table('free_days', function (Blueprint $table) {
            $table->string('busy_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('busy_types');
    }
}
