<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDomainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('url');
        });

        \Illuminate\Support\Facades\DB::table('domains')->insert([
            [
                'name' => 'trans-baza (Россия)',
                'url' => 'trans-baza.ru',
            ],
            [
                'name' => 'KINOSK (Австралия)',
                'url' => 'kinosk.com',
            ],
        ]);

        Schema::table('articles', function (Blueprint $table) {
            $table->unsignedInteger('domain_id')->nullable();
            $table->foreign('domain_id')
                ->references('id')
                ->on('domains')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('domains');
    }
}
