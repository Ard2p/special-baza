<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceRequisitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_requisites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('inn');
            $table->string('kpp')->nullable();
            $table->string('address')->nullable();
            $table->enum('type', ['entity', 'individual']);
            $table->unsignedBigInteger('invoice_id');
            $table->timestamps();
        });

        Schema::table('invoice_requisites', function (Blueprint $table) {
            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
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
        Schema::dropIfExists('invoice_requisites');
    }
}
