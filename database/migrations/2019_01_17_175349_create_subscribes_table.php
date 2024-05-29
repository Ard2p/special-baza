<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscribesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Illuminate\Support\Facades\DB::beginTransaction();

        Schema::create('subscribes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('alias');
            $table->boolean('is_system')->default(0);
            $table->boolean('can_unsubscribe')->default(0);
            $table->timestamps();
        });

        Schema::create('unsubscribe_user', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('subscribe_id');
            $table->timestamps();
        });

        Schema::create('subscribes_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('role_id');
            $table->integer('subscribe_id');
            $table->timestamps();
        });

        Schema::create('subscribe_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('html')->nullable();
            $table->integer('subscribe_id');
            $table->integer('enable_stats')->default(0);
            $table->timestamps();
        });

        Schema::create('sending_subscribes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('subscribe_id');
            $table->integer('confirm_status')->default(0);
            $table->integer('is_watch')->default(0);
            $table->timestamp('watch_at')->nullable();
            $table->timestamps();
        });



        \Illuminate\Support\Facades\DB::table('subscribes')->insert([
            [
                'name' => 'Системные уведомления',
                'alias' => 'system',
                'is_system' => 1,
                'can_unsubscribe' => 0,
            ],
            [
                'name' => 'Новости',
                'alias' => 'news',
                'is_system' => 1,
                'can_unsubscribe' => 1,
            ],
            [
                'name' => 'Статьи',
                'alias' => 'article',
                'is_system' => 1,
                'can_unsubscribe' => 1,
            ],
        ]);

        \Illuminate\Support\Facades\DB::commit();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscribes');
        Schema::dropIfExists('unsubscribe_user');
        Schema::dropIfExists('subscribes_roles');
        Schema::dropIfExists('subscribe_templates');
        Schema::dropIfExists('sending_subscribes');
    }
}
