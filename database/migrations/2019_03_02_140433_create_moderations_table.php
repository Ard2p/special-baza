<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateModerationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('moderations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('alias');
            $table->timestamps();
        });

        Schema::create('moderation_user', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('moderation_id');
        });

        DB::table('moderations')->insert([
            [
                'name' => 'Список техники',
                'alias' => 'machineries',
            ],
            [
                'name' => 'Услуги',
                'alias' => 'services',
            ],
            [
                'name' => 'Объявления',
                'alias' => 'adverts',
            ],
            [
                'name' => 'Аукцион',
                'alias' => 'auctions',
            ],
            [
                'name' => 'Продажа техники',
                'alias' => 'sales',
            ],
        ]);

        Schema::table('machineries', function (Blueprint $table) {
           $table->boolean('moderated')->default(0);
        });
        Schema::table('contractor_services', function (Blueprint $table) {
            $table->boolean('moderated')->default(0);
        });
        Schema::table('adverts', function (Blueprint $table) {
            $table->boolean('moderated')->default(0);
        });
        Schema::table('auctions', function (Blueprint $table) {
            $table->boolean('moderated')->default(0);
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('moderated')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('moderations');
    }
}
