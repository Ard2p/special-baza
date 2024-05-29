<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_hours', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('machine_id');
            $table->time('from');
            $table->time('to');
            $table->string('day_name');
            $table->boolean('is_free')->default(0);
        });
        \Illuminate\Support\Facades\DB::beginTransaction();
        foreach (\App\Machinery::all() as $machine) {
            foreach (\App\Machines\WorkHour::$day_type as $item) {
                \App\Machines\WorkHour::create([
                    'machine_id' => $machine->id,
                    'from' => \Carbon\Carbon::parse('08:00', 'Europe/Moscow'),
                    'to' => \Carbon\Carbon::parse('18:00', 'Europe/Moscow'),
                    'day_name' => $item,
                    'is_free' => 0,
                ]);
            }
        }
        \Illuminate\Support\Facades\DB::commit();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('work_hours');
    }
}
