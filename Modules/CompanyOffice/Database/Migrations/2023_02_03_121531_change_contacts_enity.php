<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\CompanyOffice\Entities\Company\Contact;
use Modules\CompanyOffice\Entities\Company\ContactEmail;
use Modules\CompanyOffice\Entities\Company\ContactPhone;

class ChangeContactsEnity extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('individual_requisites_contacts_pivot', function (Blueprint $table) {
           $table->id();
           $table->unsignedInteger('individual_requisite_id')->nullable();
           $table->string('type')->nullable();
           $table->morphs('owner');
       });
        Schema::table('individual_requisites_contacts_pivot', function (Blueprint $table) {
            $table->foreign('individual_requisite_id', 'individualReqIdx')
                ->references('id')
                ->on((new \App\User\IndividualRequisite)->getTable())->cascadeOnDelete();

        });
        Schema::table((new ContactPhone())->getTable(), function (Blueprint $table) {
            $table->unsignedBigInteger('contact_id')->nullable()->change();
            $table->unsignedInteger('individual_requisite_id')->nullable();
            $table->foreign('individual_requisite_id', 'individualReqPhoneIdx')
                ->references('id')
                ->on((new \App\User\IndividualRequisite)->getTable())->cascadeOnDelete();
        });

        Schema::table((new ContactEmail())->getTable(), function (Blueprint $table) {
            $table->unsignedBigInteger('contact_id')->nullable()->change();
            $table->unsignedInteger('individual_requisite_id')->nullable();
            $table->foreign('individual_requisite_id', 'individualReqEmailIdx')
                ->references('id')
                ->on((new \App\User\IndividualRequisite)->getTable())->cascadeOnDelete();
        });
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
