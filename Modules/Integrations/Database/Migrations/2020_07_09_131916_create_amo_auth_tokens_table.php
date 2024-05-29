<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAmoAuthTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('integrations_amo_auth_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->text('access_token');
            $table->text('refresh_token');
            $table->string('base_domain');
            $table->timestamp('expires_at')->nullable();

            $table->unsignedInteger('user_id');
            $table->timestamps();
        });

        Schema::table('integrations_amo_auth_tokens', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('amo\_amo_auth_tokens');
    }
}
