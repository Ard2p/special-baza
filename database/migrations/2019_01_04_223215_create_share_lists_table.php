<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShareListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('share_lists', function (Blueprint $table) {
            $table->increments('id');
            $table->string('url');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('type');
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::table('options')->insert([
            [
                'key' => 'send_share_by_phone'
            ],
            [
                'key' => 'send_share_by_email'
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('share_lists');
        \Illuminate\Support\Facades\DB::table('options')->whereIn('key',
            ['send_share_by_phone','send_share_by_email'])->delete();
    }
}
