<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->string('name');
            $table->string('short');
            $table->unsignedInteger('domain_id');
        });

        Schema::table('currencies', function (Blueprint $table) {
            $table->foreign('domain_id')
                ->references('id')
                ->on('domains')
                ->onDelete('cascade');
        });

        $kinosk = \Modules\RestApi\Entities\Domain::whereAlias('au')->firstOrFail();
        $tb = \Modules\RestApi\Entities\Domain::whereAlias('ru')->firstOrFail();
        DB::table('currencies')->insert([
            [
                'code' => 'RUB',
                'name' => 'Российский рубль',
                'short' => 'руб.',
                'domain_id' => $tb->id,
            ],
            [
                'code' => 'AUD',
                'name' => 'Australian dollar',
                'short' => 'aud.',
                'domain_id' => $kinosk->id,
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
        Schema::dropIfExists('currencies');
    }
}
