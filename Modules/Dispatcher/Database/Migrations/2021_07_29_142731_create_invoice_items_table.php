<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('owner_type')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('cost_per_unit')->default(0);
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('vendor_code')->nullable();
            $table->string('unit')->nullable();
            $table->unsignedBigInteger('invoice_id');
            $table->timestamps();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreign('invoice_id')
                ->references('id')
                ->on('dispatcher_invoices')
                ->onDelete('cascade');
        });

        Schema::table('custom_services', function (Blueprint $table) {
            $table->string('vendor_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoice_items');
    }
}
