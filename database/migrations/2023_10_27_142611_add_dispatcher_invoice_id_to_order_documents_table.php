<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDispatcherInvoiceIdToOrderDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_documents', function (Blueprint $table) {
            $table->foreignId('dispatcher_invoice_id')->nullable()->constrained();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_documents', function (Blueprint $table) {
            $table->dropForeign(['dispatcher_invoice_id']);
        });
    }
}
