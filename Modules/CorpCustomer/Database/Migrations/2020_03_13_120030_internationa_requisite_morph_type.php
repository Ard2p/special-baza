<?php

use App\User\EntityRequisite;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InternationaRequisiteMorphType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('international_legal_details', function (Blueprint $table) {
            $table->string('requisite_type');
            $table->unsignedBigInteger('requisite_id');
        });

        Schema::table('entity_requisites', function (Blueprint $table) {
            $table->string('register_address')->nullable()->change();
        });

        $brands = \Modules\CorpCustomer\Entities\CorpBrand::all();

        foreach ($brands as $brand) {

            $requisite = new EntityRequisite([
                'name' => $brand->full_name,
                'user_id' =>  $brand->user->id,
            ]);

            $brand->user->entityRequisites()->save($requisite);
        }
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
