<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCommentToServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('comment_label')->nullable();
        });
        Schema::table('submit_services', function (Blueprint $table) {
            $table->timestamp('start_date')->nullable();
            $table->string('address')->nullable();
        });
        Schema::create('submit_service_proposal', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('proposal_id');
            $table->integer('submit_service_id');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('comment_label');
        });

        Schema::table('submit_services', function (Blueprint $table) {
            $table->dropColumn('start_date');
            $table->dropColumn('address');
        });

        Schema::dropIfExists('submit_service_proposal');
    }
}
