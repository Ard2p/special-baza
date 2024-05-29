<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMorphRequisitesToInvoice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
              $table->string('requisite_type');
              $table->unsignedBigInteger('requisite_id');
        });

        foreach (\Modules\Orders\Entities\Payments\InvoiceRequisite::all() as $requisite) {
            $requisite->invoice->requisite()->associate($requisite);

            $requisite->invoice->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
}
