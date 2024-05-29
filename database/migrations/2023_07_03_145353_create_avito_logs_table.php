<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvitoLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('avito_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avito_order_id')->constrained();
            $table->integer('avito_request_status');
            $table->string('request_url');
            $table->json('request_body');
            $table->json('response');
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('avito_logs');
    }
}
