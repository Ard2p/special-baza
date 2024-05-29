<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Illuminate\Support\Facades\DB::beginTransaction();
        Schema::create('commissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id', false, true);
            $table->integer('percent', false, true)->default(0);
            $table->integer('enable', false, true)->default(0);
        });

        \Illuminate\Support\Facades\DB::table('options')->insert([
            [
                'key' => 'system_commission'
            ],
            [
                'key' => 'representative_commission'
            ],

        ]);
        \Illuminate\Support\Facades\DB::table('options')->insert([
            [
                'key' => 'system_cash',
                'value' => 0,
            ],
        ]);

        Schema::table('proposals', function (Blueprint $table) {
            $table->integer('regional_representative_id')->default(0);
            $table->integer('regional_representative_commission')->default(0);
            $table->integer('system_commission')->default(0);
        });

        \Illuminate\Support\Facades\DB::commit();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commissions');
    }
}
