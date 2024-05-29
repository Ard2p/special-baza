<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeadContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lead_contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('phone')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->unsignedBigInteger('lead_id');
            $table->timestamps();
        });

        Schema::table('lead_contacts', function (Blueprint $table) {
            $table->foreign('lead_id')
                ->references('id')
                ->on('dispatcher_leads')
                ->onDelete('cascade');
        });

        Schema::table('dispatcher_leads', function (Blueprint $table) {
             $table->dropForeign(['customer_id']);
             $table->string('customer_type');
             $table->string('title')->nullable();
        });

        \Modules\Dispatcher\Entities\Lead::query()->update([
            'customer_type' => \Modules\Dispatcher\Entities\Customer::class
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lead_contacts');
    }
}
