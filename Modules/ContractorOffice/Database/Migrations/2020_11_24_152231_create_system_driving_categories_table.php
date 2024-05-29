<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSystemDrivingCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driving_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('type');
            $table->text('description')->nullable();
            $table->unsignedInteger('domain_id');
        });

        Schema::table('driving_categories', function (Blueprint $table) {
            $table->foreign('domain_id')
                ->references('id')
                ->on('domains')
                ->onDelete('cascade');
        });

        $domain = \Modules\RestApi\Entities\Domain::query()->where('alias', 'ru')->first();

        \Modules\ContractorOffice\Entities\System\DrivingCategory::query()->insert([
            [
                'name' => 'Категория A I',
                'description' => 'возраст 16 лет',
                'type' => 'machinery_licence',
                'domain_id' => $domain->id
            ],
            [
                'name' => 'Категория A II',
                'description' => 'возраст 19 лет, стаж 12 месяцев автомобильной категории B',
                'type' => 'machinery_licence',
                'domain_id' => $domain->id
            ],
            [
                'name' => 'Категория A III',
                'description' => 'возраст 19 лет, стаж 12 месяцев автомобильной категории C',
                'type' => 'machinery_licence',
                'domain_id' => $domain->id
            ],
            [
                'name' => 'Категория A IV',
                'description' => 'возраст 22 года, стаж 12 месяцев автомобильной категории D',
                'type' => 'machinery_licence',
                'domain_id' => $domain->id
            ],
            [
                'name' => 'Категория B',
                'description' => 'возраст 17 лет',
                'type' => 'machinery_licence',
                'domain_id' => $domain->id
            ],
            [
                'name' => 'Категория C',
                'description' => 'возраст 17 лет',
                'type' => 'machinery_licence',
                'domain_id' => $domain->id
            ],
            [
                'name' => 'Категория D',
                'description' => 'возраст 17 лет',
                'type' => 'machinery_licence',
                'domain_id' => $domain->id
            ],
            [
                'name' => 'Категория E',
                'description' => 'возраст 17 лет',
                'type' => 'machinery_licence',
                'domain_id' => $domain->id
            ],
            [
                'name' => 'Категория F',
                'description' => 'возраст 17 лет',
                'type' => 'machinery_licence',
                'domain_id' => $domain->id
            ],


            [
                'name' => 'Категория M',
                'description' => 'мопеды и лёгкие квадрициклы',
                'type' => 'driving_licence',
                'domain_id' => $domain->id
            ],
            [
                'name' => 'Категория A1',
                'description' => 'мотоциклы с рабочим объемом двигателя внутреннего сгорания, не превышающим 125 кубических сантиметров, и максимальной мощностью, не превышающей 11 киловатт',
                'type' => 'driving_licence',
                'domain_id' => $domain->id
            ],

            [
                'name' => 'Категория A',
                'description' => 'мотоциклы',
                'type' => 'driving_licence',
                'domain_id' => $domain->id
            ],

            [
                'name' => 'Категория B1',
                'description' => 'трициклы и квадрициклы',
                'type' => 'driving_licence',
                'domain_id' => $domain->id
            ],
            [
                'name' => 'Категория B',
                'description' => 'автомобили (за исключением транспортных средств категории «A»), разрешенная максимальная масса которых не превышает 3500 килограммов и число сидячих мест которых, помимо сиденья водителя, не превышает восьми; автомобили категории «B», сцепленные с прицепом, разрешенная максимальная масса которого не превышает 750 килограммов; автомобили категории «B», сцепленные с прицепом, разрешенная максимальная масса которого превышает 750 килограммов, но не превышает массы автомобиля без нагрузки, при условии, что общая разрешенная максимальная масса такого состава транспортных средств не превышает 3500 килограммов',
                'type' => 'driving_licence',
                'domain_id' => $domain->id
            ],
            [
                'name' => 'Категория BE',
                'description' => 'автомобили категории «B», сцепленные с прицепом, разрешенная максимальная масса которого превышает 750 килограммов и превышает массу автомобиля без нагрузки; автомобили категории «B», сцепленные с прицепом, разрешенная максимальная масса которого превышает 750 килограммов, при условии, что общая разрешенная максимальная масса такого состава транспортных средств превышает 3500 килограммов стаж B 12 месяцев',
                'type' => 'driving_licence',
                'domain_id' => $domain->id
            ],

            [
                'name' => 'Категория C1',
                'description' => ' автомобили, за исключением автомобилей категории «D», разрешенная максимальная масса которых превышает 3500 килограммов, но не превышает 7500 килограммов; автомобили подкатегории «C1», сцепленные с прицепом, разрешенная максимальная масса которого не превышает 750 килограммов	18 лет',
                'type' => 'driving_licence',
                'domain_id' => $domain->id
            ],

            [
                'name' => 'Категория C1E',
                'description' => 'автомобили подкатегории «C1», сцепленные с прицепом, разрешенная максимальная масса которого превышает 750 килограммов, но не превышает массы автомобиля без нагрузки, при условии, что общая разрешенная максимальная масса такого состава транспортных средств не превышает 12 000 килограммов стаж C или C1 12 месяцев',
                'type' => 'driving_licence',
                'domain_id' => $domain->id
            ],

            [
                'name' => 'Категория C',
                'description' => '  автомобили, за исключением автомобилей категории «D», разрешенная максимальная масса которых превышает 3500 килограммов; автомобили категории «C», сцепленные с прицепом, разрешенная максимальная масса которого не превышает 750 килограммов 18 лет',
                'type' => 'driving_licence',
                'domain_id' => $domain->id
            ],

            [
                'name' => 'Категория CE',
                'description' => ' автомобили категории «C», сцепленные с прицепом, разрешенная максимальная масса которого превышает 750 килограммов стаж C 12 месяцев',
                'type' => 'driving_licence',
                'domain_id' => $domain->id
            ],

            [
                'name' => 'Категория D1',
                'description' => ' автомобили, предназначенные для перевозки пассажиров и имеющие более восьми, но не более шестнадцати сидячих мест, помимо сиденья водителя; автомобили подкатегории «D1», сцепленные с прицепом, разрешенная максимальная масса которого не превышает 750 килограммов 21 год',
                'type' => 'driving_licence',
                'domain_id' => $domain->id
            ],

            [
                'name' => 'Категория D1E',
                'description' => 'автомобили подкатегории «D1», сцепленные с прицепом, который не предназначен для перевозки пассажиров, разрешенная максимальная масса которого превышает 750 килограммов, но не превышает массы автомобиля без нагрузки, при условии, что общая разрешенная максимальная масса такого состава транспортных средств не превышает 12 000 килограммов стаж D или D1 12 месяцев',
                'type' => 'driving_licence',
                'domain_id' => $domain->id
            ],

            [
                'name' => 'Категория D',
                'description' => '  автомобили, предназначенные для перевозки пассажиров и имеющие более восьми сидячих мест, помимо сиденья водителя; автомобили категории «D», сцепленные с прицепом, разрешенная максимальная масса которого не превышает 750 килограммов 21 год',
                'type' => 'driving_licence',
                'domain_id' => $domain->id
            ],

            [
                'name' => 'Категория DE',
                'description' => 'автомобили категории «D», сцепленные с прицепом, разрешенная максимальная масса которого превышает 750 килограммов; сочлененные автобусы, дуобусы. стаж D 12 месяцев',
                'type' => 'driving_licence',
                'domain_id' => $domain->id
            ],

            [
                'name' => 'Категория Tm',
                'description' => 'трамваи',
                'type' => 'driving_licence',
                'domain_id' => $domain->id
            ],

            [
                'name' => 'Категория Tb',
                'description' => 'троллейбусы (при DE — дуобусы)',
                'type' => 'driving_licence',
                'domain_id' => $domain->id
            ],
        ]);


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system\_driving_categories');
    }
}
