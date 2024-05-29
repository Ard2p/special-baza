<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->default('transbaza');
            $table->string('alias')->default('transbaza');
            $table->timestamps();
        });
        \Illuminate\Support\Facades\DB::table('chats')->insert([
            [
                'name' => 'TRANSBAZA',
                'alias' => 'transbaza',
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats');
    }
}
