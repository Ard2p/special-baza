<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMediaOwnerToOrderMediasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_media', function (Blueprint $table) {
            $table->nullableMorphs('owner');
            $table->text('url')->change();
            $table->string('original_path')->nullable();
            $table->dropForeign(['order_component_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_medias', function (Blueprint $table) {

        });
    }
}
