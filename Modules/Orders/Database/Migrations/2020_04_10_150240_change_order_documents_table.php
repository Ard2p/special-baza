<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeOrderDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_documents', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->string('order_type');
        });

        \Modules\Orders\Entities\OrderDocument::query()->update([
            'order_type' => \Modules\Orders\Entities\Order::class
        ]);



    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
