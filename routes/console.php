<?php

use App\Article;
use App\Events\OrderUpdatedEvent;
use App\Helpers\RequestHelper;
use App\Http\Controllers\Avito\Events\OrderChangedEvent;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Jobs\AvitoNotificaion;
use App\Support\Gmap;
use Carbon\Carbon;
use GuzzleHttp\RequestOptions;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Str;
use Modules\CompanyOffice\Entities\CashRegister;
use Modules\CompanyOffice\Entities\Company\ContactPhone;
use Modules\CompanyOffice\Services\CompaniesService;
use Modules\ContractorOffice\Entities\System\TariffGrid;
use Modules\ContractorOffice\Entities\System\TariffUnitCompare;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Services\VehicleService;
use Modules\Dispatcher\Entities\Customer;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\Payments\InvoicePay;
use Modules\Orders\Entities\SystemPayment;
use Modules\Orders\Services\AvitoPayService;
use Modules\PartsWarehouse\Entities\Warehouse\Part;
use Modules\RestApi\Entities\Domain;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/
Artisan::command('import_clients', function () {

   $clients = (new \Rap2hpoutre\FastExcel\FastExcel())->import(storage_path('app/client.xlsx'));
   $branch = \Modules\CompanyOffice\Entities\Company\CompanyBranch::query()->findOrFail(2127);
    $req = $branch->requisite->first();
    DB::beginTransaction();
    foreach ($clients as $client) {
        if($client['ИНН'])
            continue;

        /** @var Customer $customer */
        $customer =  Customer::create([
            'company_name'      => $client['Имя'],
            'address'           => $client['Адрес'],
            'region_id'         => $branch->region_id,
            'city_id'           => $branch->city_id,
            'email'             => 'client@prokatnk.com',
            'contact_person'    => $client['Имя'],
            'contact_position'  => null,
            'phone'             => trimPhone($client['Телефон']),
            'creator_id'        => $branch->creator_id,
            'domain_id'         =>$branch->domain->id,
            'company_branch_id' => $branch->id,
        ]);

        $data = explode(' ', trim($client['Имя']));
        $surname = $data[0] ?? null;
$firstName = $data[1]?? null;
$middlename = $data[2]?? null;
        $customer->addIndividualRequisites([
            'company_branch_id' => $branch->id,
            'type' => \App\User\IndividualRequisite::TYPE_PERSON,
            'gender' => '',
            'birth_date' => Carbon::parse($client['Дата рождения']),
            'passport_number' => $client['Серия и номер документа'],
            'register_address' => $client['Адрес'],
            'full_name' => $client['Имя'],
            'firstname' => $firstName,
            'middlename' => $middlename,
            'surname' => $surname,
           // 'passport_date' => '',
           // 'issued_by' => '',
        ]);
        $contract = $customer->generateContract($req);
    }
    DB::commit();
});

Artisan::command('test_mango', function () {
    $mango = \Modules\Integrations\Entities\MangoTelephony::query()->first();

    collect($mango->getStats(now()->subHours(3), now()))
        ->pipe(fn($result) => collect($result['data'][0]['list']))
        ->map(fn($call) => [
            'context_type' => $call['context_type'],
            'caller_name' => $call['caller_name'],
            'caller_number' => $call['caller_number'],
            'called_number' => $call['called_number'],
            'recall_status' => $call['recall_status'],
            'context_status' => $call['context_status'],
            'entry_id' => $call['entry_id'],
            'context_start_time' => $call['context_start_time'],
        ])
        ->each(fn($data) => $mango->parseCall($data));
});

Artisan::command('generate_pays', function () {
    DB::beginTransaction();

    foreach (\Modules\Orders\Entities\Payments\InvoicePay::all() as $model) {
        $invoice = $model->invoice;
        if (!$invoice)
            continue;

        $owner = $invoice->owner;
        if (!$owner)
            continue;
        if ($owner instanceof Order) {
            $position = $owner->components()->first();
            if ($position) {
                $baseId = $position->machinery_base_id;
            }
        }
        if (CashRegister::query()->where('invoice_pay_id', $model->id)->exists()) {
            CashRegister::query()->where('invoice_pay_id', $model->id)->delete();
        }

        $cashRegister = new CashRegister([
            'sum'               => $model->sum,
            'stock'             => $model->type,
            'type'              => $model->operation,
            'company_branch_id' => $owner->company_branch_id,
            'machinery_base_id' => $baseId ?? null,
            'comment'           => $owner instanceof Order
                ? "Сделка #{$owner->internal_number}"
                : "Заявка #{$owner->internal_number}",
            'invoice_pay_id'    => $model->id,
            'ref'               => [
                'id'       => $owner->id,
                'instance' => $owner instanceof Order
                    ? 'order'
                    : 'lead'
            ],
            'created_at'        => Carbon::parse($model->date->format('Y-m-d') . ' ' . $model->created_at->format('H:i'))
        ]);
        $cashRegister->timestamps = false;
        $cashRegister->created_at =
            Carbon::parse($model->date->format('Y-m-d') . ' ' . $model->created_at->format('H:i'));
        $cashRegister->save();
    }

    DB::commit();
});

Artisan::command('import_models', function () {

    $models = (new \Rap2hpoutre\FastExcel\FastExcel())->import(storage_path('inv.xlsx'));


    DB::beginTransaction();

    foreach ($models as $model) {

        if (!$model['category'])
            continue;

        $category = \App\Machines\Type::query()->where('name', trim($model['category']))->first();
        $brand = \App\Machines\Brand::query()->where('name', trim($model['brand']))->first();
        if (!$brand) {
            $brand = \App\Machines\Brand::create([
                'name' => trim($model['brand'])
            ]);
        }
        if (!$category) {
            $category = \App\Machines\Type::create([
                'type'             => 'equipment',
                'name'             => trim($model['category']),
                'name_style'       => $model['category'],
                'eng_alias'        => generateChpu($model['category']),
                'alias'            => generateChpu($model['category']),
                //'photo' => $request->input('photo'),
                'vin'              => 0,
                'rent_with_driver' => 0,
                'licence_plate'    => 0,
            ]);
        }
        \App\Machines\MachineryModel::create([
            'category_id' => $category->id,
            'brand_id'    => $brand->id,
            'name'        => trim($model['model']),
            'alias'       => generateChpu($model['model'])
        ]);
    }

    dd(\App\Machines\Type::query()->orderBy('id', 'desc')->take(10)->get());
    DB::commit();
});
Artisan::command('migrate_req', function () {

    DB::beginTransaction();

    foreach (\Modules\CompanyOffice\Entities\Company\CompanyBranch::all() as $companyBranch) {
        $requisite = $companyBranch->entity_requisites()->first();
        if (!$requisite)
            $requisite = $companyBranch->international_legal_requisites()->first();


        /** @var \Modules\Orders\Entities\Order $order */
        foreach ($companyBranch->orders as $order) {

            if ($order->contractor_id !== $companyBranch->id)
                continue;

            if ($requisite) {
                $order->contractorRequisite()->associate($requisite);
                $order->save();

            }
        }
    }
    DB::commit();
});


Artisan::command('import_europlatform', function () {

    $vehicles = (new \Rap2hpoutre\FastExcel\FastExcel())->import(storage_path('app/public/europlatform.xlsx'));

    DB::beginTransaction();
    $branch = \Modules\CompanyOffice\Entities\Company\CompanyBranch::query()->findOrFail(2032);
    config()->set('request_domain', Domain::query()->whereAlias('ru')->firstOrFail());
    foreach ($vehicles as $vehicle) {
        if (empty($vehicle['category'])) {
            continue;
        }
        $category = \App\Machines\Type::query()->whereName($vehicle['category'])->firstOrFail();


        $brand = \App\Machines\Brand::query()->whereName($vehicle['brand'])->firstOrFail();

        $model = \App\Machines\MachineryModel::query()->whereName($vehicle['model'])->firstOrFail();

        $service = new VehicleService($branch);
        $attributes = [];
        $model->characteristics->each(function ($item) use
        (
            &
            $attributes
        ) {

            $attributes[$item->id] = $item->pivot->value;

        });
        $machine = $service->setData([

            'address'                   => $vehicle['address'],
            'delivery_radius'           => 100,
            'region_id'                 => $branch->region_id,
            'city_id'                   => $branch->city_id,
            'board_number'              => $vehicle['board_number'],
            'creator_id'                => $branch->creator_id,
            'type'                      => $category->id,
            'category_id'               => $category->id,
            'optional_attributes'       => $attributes,
            'brand_id'                  => $brand->id,
            'model_id'                  => $model->id,
            'serial_number'             => $vehicle['serial_number'],
            'prices'                    => [
                [
                    'cost_per_shift' => $vehicle['cashless_vat'],
                    'cost_per_hour'  => $vehicle['cashless_vat'],
                    'type'           => \Modules\ContractorOffice\Entities\Vehicle\Price::TYPE_CASH,

                ],
                [
                    'cost_per_shift' => $vehicle['cashless_vat'],
                    'cost_per_hour'  => $vehicle['cashless_vat'],
                    'type'           => \Modules\ContractorOffice\Entities\Vehicle\Price::TYPE_CASHLESS_VAT,

                ],
                [
                    'cost_per_shift' => $vehicle['cashless_vat'],
                    'cost_per_hour'  => $vehicle['cashless_vat'],
                    'type'           => \Modules\ContractorOffice\Entities\Vehicle\Price::TYPE_CASHLESS_WITHOUT_VAT,

                ],
            ],
            'tariff_type'               => \Modules\ContractorOffice\Entities\System\Tariff::TIME_CALCULATION,
            'description'               => '',
            'market_price'              => $vehicle['market_price'],
            'market_price_currency'     => 'rub',
            'currency'                  => 'RUB',
            'free_delivery_distance'    => 0,
            'delivery_cost_over'        => 0,
            'is_rented'                 => 1,
            'price_includes_fas'        => true,
            'is_contractual_delivery'   => true,
            'contractual_delivery_cost' => $vehicle['contractual_delivery_cost'],
            'shift_duration'            => 24,
            'name'                      => $vehicle['name'],
            'min_order_type'            => 'shift',
            'min_order'                 => $vehicle['min_order'],
            'number'                    => null,
            'scans'                     => [],
            'photo'                     => [],
            'coordinates'               => '55.74107070,37.59964950',

        ])->createVehicle();
    }
    DB::commit();
    dd($vehicles);
});
Artisan::command('change_h', function () {

    \App\Machinery::query()->where('company_branch_id', 2069)
        ->update([
            'change_hour' => 24
        ]);
});

Artisan::command('move_avito_ads', function () {

    $machineries = \App\Machinery::query()->whereNotNull('avito_id')->get();

    $insert = [];

    $createdAt = now()->format('Y-m-d H:i:s');

    foreach ($machineries as $machinery) {
        dump($machinery->id);
        dump($machinery->avito_id);
        dump('-------------------------------');
        $insert[] = [
            'avito_id' => $machinery->avito_id,
            'machinery_id' => $machinery->id,
            'created_at' => $createdAt,
        ];
    }

    dump($insert);
    DB::table('avito_ads')->upsert(
        $insert,
        ['avito_id', 'machinery_id'],
        ['avito_id', 'created_at']
    );
});

Artisan::command('import_demo_calls', function () {

    $statuses = [
        'Success',
        'Busy',
        'NotAvailable',
    ];

    DB::beginTransaction();

    for ($i = 0; $i < 20; $i++) {
        $rand = rand(1, 3);
        $rand2 = rand(0, 1);
        $call = [
            'phone'         => rand(390000000000, 399999999999),
            'manager_phone' => 391111111111,
            'link'          => "https://api.trans-baza.ru/files/call{$rand}.m4a",
            'call_id'       => null,
            'status'        => $statuses[rand(0, 2)],
            'raw_data'      => [
                'type'      => ($rand2
                    ? 'in'
                    : 'out'),
                'diversion' => 390342687161,
            ],
            'important'     => false,
            'bind_type'     => null,
            'bind_id'       => null,
            'listened'      => 0,
            'company_id'    => 2053,
        ];

        \Modules\Integrations\Entities\Telpehony\TelephonyCallHistory::create($call);
    }

    DB::commit();

});

Artisan::command('assign_users_to_branches', function () {


    foreach (\Modules\CompanyOffice\Entities\Company\CompanyBranch::all() as $companyBranch) {
        /** @var \App\User $user */
        $user = $companyBranch->user;
        $user->branches()->sync([
            $companyBranch->id => [
                'role' => \Modules\CompanyOffice\Services\CompanyRoles::ROLE_ADMIN
            ]
        ]);

        \Modules\CompanyOffice\Services\CompanyRoles::syncRoleWithPermissions($user, $companyBranch->id, \Modules\CompanyOffice\Services\CompanyRoles::ROLE_ADMIN);


    }

    /* $user = \App\User::whereEmail('fms@c-cars.tech')->firstOrFail();
     \Modules\Integrations\Entities\Integration::whereName('FMS')->first()->update(['parent_id' => $user->id]);*/
});


Artisan::command('update_integration', function () {

    foreach (\Modules\CompanyOffice\Entities\Company::all() as $company) {
        $company->update(
            ['options' => $company->getDefaultOptions()]
        );
    }
});


Artisan::command('test_azure_blob', function () {
    // $data =  Storage::disk('public_disk')->get('images/1_en.png');
    $img = Storage::disk()->url('my_image.png');
    /*   foreach (\App\Machinery::all() as $machine) {
           foreach ($machine->photos as $photo) {
               if( Storage::disk('public_disk')->exists($photo)) {
                   $data =  Storage::disk('public_disk')->get($photo);
                   Storage::disk()->put( $photo, $data);
               }
           }
       }*/
    // $img = Storage::disk()->url('vehicles/my_image.png');

    dd($img);
});

Artisan::command('migrate_to_azure_blob', function () {

    foreach (\App\Machinery::all() as $machine) {
        foreach ($machine->photos as $photo) {
            if (Storage::disk('public_disk')->exists($photo)) {
                $data = Storage::disk('public_disk')->get($photo);
                Storage::disk()->put($photo, $data);
            }
        }

        $scans = json_decode($machine->scans, true);
        $arr = [];
        foreach ($scans as $photo) {
            if (Storage::disk('public_disk')->exists($photo)) {
                $data = Storage::disk('public_disk')->get($photo);
                $photo = str_replace("/{$machine->id}", "/{$machine->id}/scans", $photo);
                Storage::disk()->put($photo, $data);
                $arr[] = $photo;

            }
        }
        $machine->update(['scans' => json_encode($arr)]);
    }

    /*  foreach (Article::all() as $article) {
          if (Storage::disk('public_disk')->exists($article->image)) {
              $data = Storage::disk('public_disk')->get($article->image);
              Storage::disk()->put($article->image, $data);
          }

          $thumb = str_replace('images/', 'thumbnail/', $article->image);

          if (Storage::disk('public_disk')->exists($thumb)) {
              $data = Storage::disk('public_disk')->get($thumb);
              Storage::disk()->put($thumb, $data);
          }
      }

      foreach (\Modules\Orders\Entities\OrderDocument::all() as $document) {

          $path = str_replace(config('app.url'), '', $document->url);

          if (Storage::disk('public_disk')->exists($path)) {
              $data = Storage::disk('public_disk')->get($path);
              Storage::disk()->put($path, $data);
          }
      }

      foreach (\App\Support\Document::all() as $document) {


          if (Storage::disk('public_disk')->exists($document->url)) {
              $data = Storage::disk('public_disk')->get($document->url);
              Storage::disk()->put($document->url, $data);
          }
      }

      foreach (\App\Machines\Type::all() as $type) {

          $path = $type->photo;

          if (Storage::disk('public_disk')->exists($path)) {
              $data = Storage::disk('public_disk')->get($path);
              Storage::disk()->put($path, $data);
          }
      }*/
});


Artisan::command('import_regions', function () {
    $cities = (new \Rap2hpoutre\FastExcel\FastExcel())->import(storage_path('kazahstan.xlsx'));

    // dd($cities);

    $country = \App\Support\Country::query()->whereAlias('russia')->first();
    DB::beginTransaction();
    foreach ($cities as $city) {

        $regionName = trim($city['region']);

        $region = \App\Support\Region::query()->where('name', $regionName)->first();
        if (!$region) {
            $region = \App\Support\Region::create([
                'name'       => $regionName,
                'alias'      => generateChpu($regionName),
                'number'     => 0,
                'country_id' => $country->id
            ]);
        }

        \App\City::create([
            'name'        => $city['city'],
            'alias'       => generateChpu($city['city']),
            'region_id'   => $region->id,
            'coordinates' => null //map::getCoordinatesByAddress($region->name, $city['place'], 'Thailand'),
        ]);

    }
    // dd(\App\Support\Region::query()->where('name', 'like', '%Казах%')->get());
    DB::commit();
});

Artisan::command('import_italy', function () {
    $italy =
        (new \Rap2hpoutre\FastExcel\FastExcel())->import(\Illuminate\Support\Facades\Storage::path('public/hhh.xlsx'));
    $array = $italy->groupBy('province');


    DB::beginTransaction();

    /* $domain = \Modules\RestApi\Entities\Domain::create([
         'name' => 'Italy',
         'alias' => 'it',
         'url' => 'kinosk.com',
         'options' => [
             'phone_mask' => '+39 (###)-###-##-##',
             'phone_code' => '+39',
             'phone_digits' => 12,
             'default_locale' => 'it',
             'available_locales' => ['it', 'en'],
             'register_mask' => '(###)-###-##-##',
         ]
     ]);


     $country = \App\Support\Country::create([
         'name' => 'Italy',
         'alias' => 'it',
         'domain_id' => $domain->id
     ]);*/
    $country = \App\Support\Country::query()->where('alias', 'it')->firstOrFail();
    foreach ($array as $province => $cities) {
        //dd($province);
        $fields = [
            'name'       => trim($province),
            'alias'      => generateChpu($province),
            'number'     => 0,
            'country_id' => $country->id
        ];
        $region = \App\Support\Region::query()
            ->where('country_id', $country->id)
            ->where('name', trim($province))
            ->first();

        if (!$region) {
            $region = \App\Support\Region::create($fields);
        }


        foreach ($cities as $city) {
            $ex = \App\City::query()->where('region_id', $region->id)
                ->where('name', trim($city['city']))->first();
            if ($ex) {
                continue;
            }
            \App\City::create([
                'name'      => trim($city['city']),
                'alias'     => generateChpu($city['city']),
                'region_id' => $region->id,
                //  'coordinates' => Gmap::getCoordinatesByAddress($region->name, $city['city'], 'Italy'),
            ]);
        }
    }
    DB::commit();

});
Artisan::command('import_thailand', function () {

    $thailand =
        (new \Rap2hpoutre\FastExcel\FastExcel())->import(\Illuminate\Support\Facades\Storage::path('public/import/thailand.xlsx'));
    $regions = ($thailand->groupBy('district1'));

    DB::beginTransaction();

    $domain = \Modules\RestApi\Entities\Domain::create([
        'name'  => 'Thailand',
        'alias' => 'thai',
        'url'   => 'kinosk.com'
    ]);


    $country = \App\Support\Country::create([
        'name'      => 'Thailand',
        'alias'     => 'thailand',
        'domain_id' => $domain->id
    ]);

    \Modules\RestApi\Entities\Currency::create([
        'code'      => 'THB',
        'name'      => 'Thai baht',
        'short'     => 'THB',
        'domain_id' => $domain->id,
    ]);

    foreach ($regions as $region => $cities) {
        $region = \App\Support\Region::create([
            'name'       => $region,
            'alias'      => generateChpu($region),
            'number'     => 0,
            'country_id' => $country->id
        ]);

        foreach ($cities as $city) {
            \App\City::create([
                'name'        => $city['place'],
                'alias'       => generateChpu($city['place']),
                'region_id'   => $region->id,
                'coordinates' => null //map::getCoordinatesByAddress($region->name, $city['place'], 'Thailand'),
            ]);
        }
    }
    DB::commit();
});
Artisan::command('add_domain_options', function () {
    $options = [
        'ru'   => [
            'phone_mask'        => '+7 (###)-###-##-##',
            'phone_code'        => '+7',
            'phone_digits'      => 11,
            'default_locale'    => 'ru',
            'available_locales' => ['ru'],
            'register_mask'     => '(###)-###-##-##',
        ],
        'au'   => [
            'phone_mask'        => '+61-#-####-####',
            'phone_code'        => '+61',
            'phone_digits'      => 11,
            'default_locale'    => 'en',
            'available_locales' => ['en'],
            'register_mask'     => '#-####-####',
        ],
        'thai' => [
            'phone_mask'        => '+66-#-###-####',
            'phone_code'        => '+66',
            'phone_digits'      => 10,
            'default_locale'    => 'th',
            'available_locales' => ['th', 'en'],
            'register_mask'     => '#-###-####',
        ],
    ];

    $names = [
        'ru'   => [
            'name' => 'Россия',
        ],
        'au'   => [
            'name' => 'Australia',
        ],
        'thai' => [
            'name' => 'ไทย',
        ],
    ];

    foreach ($options as $domain => $option) {
        $item = \Modules\RestApi\Entities\Domain::whereAlias($domain)->firstOrFail();

        $item->update([
            'options' => $option
        ]);
    }

    foreach ($names as $domain => $name) {
        $item = \Modules\RestApi\Entities\Domain::whereAlias($domain)->firstOrFail();

        $item->update([
            'name' => $name['name']
        ]);
    }
});


Artisan::command('sitemap_generate', function () {

    dispatch(new \App\Jobs\SitemapGenerate());
});
Artisan::command('checkPayments', function () {

    $payments = \App\Finance\Payment::where('status', 0)->get();

    $api = new \App\Service\AlphaBank();
    foreach ($payments as $payment) {

        $response = $api->getOrderStatus($payment->order_id);


        DB::beginTransaction();

        if (($response['errorCode'] == 0)) {

            $payment->update([
                'status'   => $response['orderStatus'],
                'response' => json_encode($response)
            ]);

            if ($response['orderStatus'] == 2) {
                $payment->finance_transaction->accept_from_bank();
                (new \App\Service\Subscription())->newPaymentNotification($payment->finance_transaction);
            }

        } else {
            Log::info(json_encode($response));
        }
        DB::commit();
    }

    $payments = \App\Finance\Payment::whereIn('status', [3, 4, 6])->whereHas('finance_transaction', function ($q) {
        return $q->where('status', '!=', 2);
    })->get();

    foreach ($payments as $payment) {

        // $response = $api->getOrderStatus($payment->order_id);
        DB::beginTransaction();
        /*   $payment->update([
               'status' => $response['orderStatus'],
               'response' => json_encode($response)
           ]);*/
        if ($payment->finance_transaction->status !== 2) {
            $payment->finance_transaction->update([
                'status' => 2
            ]);
        }

        DB::commit();


    }

});

Artisan::command('test_bank', function () {

    /** @var AvitoPayService $avitoService */
    $avitoService = app(AvitoPayService::class);

    dd(
        $avitoService->registerPayment(
            sum: 9000,
            description: 'Тест',
            details: [/*order data */],
            successUrl: 'https://trans-baza.ru', //
            failUrl: null,
            modelOwner: null,
        )
    );

});
Artisan::command('checkAvitoPayments', function () {
    /** @var AvitoPayService $avitoService */
    $avitoService = app(AvitoPayService::class);

    SystemPayment::query()
        ->where('type', 'avito')
        ->whereIn('status', [0, 1, 5])
        ->get()
        ->each(function (SystemPayment $systemPayment) use ($avitoService) {

            DB::beginTransaction();
            $response = $avitoService->getStatus($systemPayment);

            if ($response && $response['errorCode'] == 0) {

                if ($response['orderStatus'] == 2) {
                    $pay = new InvoicePay([
                        'type' => 'cashless',
                        'date' => Carbon::now()->format('Y-m-d H:i:s'),
                        'sum' => $systemPayment->sum,
                        'operation' => 'in',
                        'method' => 'card',
                        'tax_percent' => 0,
                        'tax' => 0,
                    ]);
                    $systemPayment->owner->pays()->save($pay);
                    $order = $systemPayment->owner->owner->refresh();
                    OrderChangedEvent::dispatch($order, AvitoOrder::STATUS_PREPAID);

                    $message = "Заказ с Авито #$order->external_id оплачен клиентом.";

                    dispatch(new AvitoNotificaion($order,$message))->delay(Carbon::now()->addSeconds(5));
                }

                $systemPayment->update([
                    'status' => $response['orderStatus']
                ]);
            }

            DB::commit();


        });
});

Artisan::command('checkHolds', function () {

    $payments = \App\Finance\HoldPayment::where('status', 0)->get();

    $api = new \App\Service\AlphaBank();
    foreach ($payments as $payment) {

        $response = $api->getOrderStatus($payment->order_id);


        DB::beginTransaction();
        $payment->update([
            'status'   => $response['orderStatus'],
            'response' => json_encode($response)
        ]);
        if (($response['errorCode'] == 0)) {

            if ($response['orderStatus'] == 1) {
                $payment->finance_transaction->accept_from_bank(true);
                $request = request();
                $request->merge(json_decode($payment->request_params, true));


                $service = new \App\Service\OrderService($request);


                $errors = $service->validateErrors()->getErrors();

                $sum = $service->search()->setNeedleUser($request->user_id)->getOrderSum();

                if ($sum > $payment->user->getBalance('customer')) {
                    $errors[] = 'Данные не актуальны.';

                }
                if ($errors) {
                    \Illuminate\Support\Facades\Log::info(json_encode($errors));
                    $payment->refuse();
                    continue;
                }

                $service->forUser($payment->user->id)->createOrder();

                $payment->update([
                    'proposal_id' => $service->created_proposal->id
                ]);

                //  (new \App\Service\Subscription())->newPaymentNotification($payment->finance_transaction);
            }

        }
        DB::commit();
    }

});


Artisan::command('makeDocs', function () {

    $docs = [
        'Договор на оказание услуг',
        'Акт выполненных работ',
        'Счет-Фактура',
    ];

    foreach ($docs as $doc) {
        $check = \App\Support\DocumentType::where('name', $doc)->first();
        if ($check) {
            continue;
        }
        \App\Support\DocumentType::create([
            'name' => $doc,
        ]);
    }

});

Artisan::command('trim_m_number', function () {
    \Illuminate\Support\Facades\DB::beginTransaction();
    foreach (\App\Machinery::all() as $machine) {

        $machine->number = trim(str_replace(' ', '', $machine->number));
        $machine->save();
    }
    DB::commit();
});
Artisan::command('generate_chpu', function () {
    \Illuminate\Support\Facades\DB::beginTransaction();
    foreach (\App\Machinery::all() as $machine) {
        $category = $machine->_type->name ?? '';
        $city = $machine->city->name ?? '';
        $brand = $machine->brand->name ?? '';

        $machine->alias = \App\Article::generateChpu("{$brand} {$machine->user_id}-{$machine->id}");
        $machine->save();
    }
    DB::commit();
});
Artisan::command('create_article_subscribe', function () {
    \Illuminate\Support\Facades\DB::beginTransaction();


    foreach (\App\Article::whereIsStatic(0)->get() as $article) {
        if ($article->is_news === 1) {
            $subscribe = \App\User\Subscribe::whereAlias('news')->first();
        }
        if ($article->is_article === 1) {
            $subscribe = \App\User\Subscribe::whereAlias('article')->first();
        }
        \App\User\SubscribeTemplate::firstOrCreate([
            'name'         => $article->title,
            'html'         => $article->content,
            'article_id'   => $article->id,
            'subscribe_id' => $subscribe->id,
            'enable_stats' => 1,
        ]);
    }


    DB::commit();
});


Artisan::command('makeRoles', function () {

    $roles = [
        'admin'         => 'Администратор',
        'performer'     => 'Исполнитель',
        'customer'      => 'Заказчик',
        'content_admin' => 'Администратор контента',
        'widget'        => 'Виджет',
    ];

    foreach ($roles as $key => $role) {
        $check = \App\Role::where('alias', $key)->first();
        if ($check) {
            continue;
        }
        \App\Role::create([
            'name'  => $role,
            'alias' => $key,
        ]);
    }

});

Artisan::command('create_tbc', function () {

    foreach (\App\User::all() as $user) {
        \App\User\Balance::firstOrCreate([
            'user_id' => $user->id,
            'type'    => \App\User\Balance::type('tbc'),
        ]);
    }

});

Artisan::command('insertInTable', function () {

    $brands = json_decode(trim(\Illuminate\Support\Facades\Storage::get('/public/import/brands.json')), true);

    $options = json_decode(trim(\Illuminate\Support\Facades\Storage::get('/public/import/options.json')), true);

    $regions = json_decode(trim(\Illuminate\Support\Facades\Storage::get('/public/import/regions.json')), true);

    $cities = json_decode(trim(\Illuminate\Support\Facades\Storage::get('/public/import/cities.json')), true);

    $city_codes = json_decode(trim(\Illuminate\Support\Facades\Storage::get('/public/import/city_codes.json')), true);

    $faqs = json_decode(trim(\Illuminate\Support\Facades\Storage::get('/public/import/faqs.json')), true);

    $gibdd_codes = json_decode(trim(\Illuminate\Support\Facades\Storage::get('/public/import/gibdd_codes.json')), true);

    $static_contents =
        json_decode(trim(\Illuminate\Support\Facades\Storage::get('/public/import/static_contents.json')), true);

    $support_categories =
        json_decode(trim(\Illuminate\Support\Facades\Storage::get('/public/import/support_categories.json')), true);

    $types = json_decode(trim(\Illuminate\Support\Facades\Storage::get('/public/import/types.json')), true);

    DB::table('brands')->insert($brands);
    DB::table('options')->insert($options);
    DB::table('regions')->insert($regions);
    DB::table('cities')->insert($cities);
    DB::table('city_codes')->insert($city_codes);
    DB::table('faqs')->insert($faqs);
    DB::table('gibdd_codes')->insert($gibdd_codes);
    DB::table('static_contents')->insert($static_contents);
    DB::table('support_categories')->insert($support_categories);
    DB::table('types')->insert($types);
});


Artisan::command('makeAdmin', function () {

    $email = $this->ask('What is your email?');

    $user = \App\User::where('email', $email)->firstOrFail();
    $role_id = \App\Role::where('alias', 'admin')->firstOrFail()->id;

    $user->roles()->attach($role_id);

});

Artisan::command('makeTypeStyle', function () {


});
Artisan::command('generate_spec', function () {
    foreach (\App\City::all() as $city) {
        foreach (\App\Machines\Type::all() as $cat) {
            $content = \App\Support\SeoContent::where('type_id', $cat->id)->whereCityId($city->id)->first();
            if (!$content) {
                $data = [];
                $codes = [
                    903, 905, 906, 909, 951, 953, 960, 961, 962, 963, 964, 965, 966, 967, 968, 910, 911, 912, 913, 914, 915,
                    916, 917, 918, 919, 980, 981, 982, 983, 984, 985, 987, 988, 989, 920, 921, 922, 923, 924, 925, 926, 927,
                    928, 929, 900, 901, 902, 904, 908, 950, 951, 952, 953, 958, 977, 991, 992, 993, 994, 995, 996, 999, 999,
                ];

                $i = rand(2, 5);

                for ($a = 0; $a < $i; $a++) {
                    $code = $codes[array_rand($codes)];
                    $str = '';
                    for ($y = 0; $y < 7; $y++) {
                        $str .= rand(1, 9);
                    }
                    $data[$a]['phone'] = "7{$code}{$str}";
                    $data[$a]['name'] = \App\Support\SeoContent::$names[array_rand(\App\Support\SeoContent::$names)];
                }
                $content = \App\Support\SeoContent::create([
                    'fields'  => json_encode($data),
                    'city_id' => $city->id,
                    'type_id' => $cat->id,
                ]);
            }

        }
    }
});

Artisan::command('get_state', function () {
    $data = [];
    $tinkoff = new \App\Finance\TinkoffMerchantAPI();

    foreach (\App\Finance\TinkoffPayment::all() as $payment) {
        $data[] = $tinkoff->getState([
            'PaymentId' => $payment->payment_id
        ])->response;
    }
    dd($data);
});


Artisan::command('createBranch', function () {
    DB::beginTransaction();

    $user = \App\User::query()->where('email', 'info@profrent.su')->firstOrFail();
    $domain = Domain::query()->where('alias', 'ru')->firstOrFail();
    /** @var \Modules\CompanyOffice\Entities\Company $company */
    $company = \Modules\CompanyOffice\Entities\Company::query()->where('alias', 'profrent')->firstOrFail();
    $firstBranch = $company->branches()->first();
    $service = new CompaniesService($company);

    $service->createBranch('Республиканская', $firstBranch->region_id, $firstBranch->city_id, $user->id);
    $service->createBranch('Сервисный центр', $firstBranch->region_id, $firstBranch->city_id, $user->id);

    DB::commit();
});
Artisan::command('installApp', function () {
    \Illuminate\Support\Facades\DB::beginTransaction();
    Artisan::call('migrate');
    Artisan::call('db:seed');
    Artisan::call('storage:link');
    Artisan::call('insertInTable');
    Artisan::call('makeDocs');
    Artisan::call('makeRoles');
    \Illuminate\Support\Facades\DB::commit();
});
Artisan::command('add_australia', function () {

    $import = \Illuminate\Support\Facades\Storage::get('public/import/au.json');
    $cities = json_decode($import, true);
    foreach ($cities as $city) {
        $region = \App\Support\Region::whereName($city['admin'])->first();
        if (!$region) {
            $region = \App\Support\Region::create([
                'name'       => $city['admin'],
                'number'     => 0,
                'country_id' => 5,
                'alias'      => generateChpu($city['admin'])
            ]);
        }

        \App\City::create([
            'name'        => $city['city'],
            'alias'       => generateChpu($city['city']),
            'region_id'   => $region->id,
            'coordinates' => "{$city['lat']},{$city['lng']}"
        ]);
    }
});

Artisan::command('kinosk_articles', function () {
    $articles = \App\Support\ArticleLocale::all();

    function replaceOld($str)
    {
        $words = [
            'transbaza'     => 'kinosk',
            'trans-baza.ru' => 'kinosk.com',
            'TRANS-BAZA.RU' => 'KINOSK.COM',
            'TRANSBAZA'     => 'KINOSK',
            'Transbaza'     => 'Kinoks',
            'TRANS-BAZA'    => 'KINOSK',
            'trans-baza'    => 'kinosk',
            'Trans-baza'    => 'Kinosk',
        ];
        foreach ($words as $old => $new) {
            $str = str_replace($old, $new, $str);
        }

        return $str;
    }

    foreach ($articles as $article) {
        $article->title = replaceOld($article->title);
        $article->keywords = replaceOld($article->keywords);
        $article->description = replaceOld($article->description);
        $article->h1 = replaceOld($article->h1);
        $article->image_alt = replaceOld($article->image_alt);
        $article->content = replaceOld($article->content);
        $article->save();
    }
});

Artisan::command('wialon', function () {
    $wialon = \Modules\Integrations\Entities\Wialon::query()->findOrFail(2);

    //$wialon->getToken();die;
    //dd($wialon->searchItems('avl_resource'));
    dd($wialon->createReportTemplate());

    $resource = ($wialon->getResourceByName($wialon->login));

    // dd($wialon->searchItems('avl_resource'));
    //  $wialon->loadVehicles();
    $items = $wialon->searchItems()['items'];

    // dd($wialon->accountInfo());

    foreach ($items as $item) {
        //  dd($item);
        //dd($wialon->searchItem($item['id']));
        //  echo  '<pre>';
        dd($wialon->generateReport($item['id'], $resource['id']));
        die;
    }

});

Artisan::command('up_vehicles', function () {
    \App\Machinery::query()->update(['is_rented' => 1]);
});

Artisan::command('test_telematic', function () {
    $vehicles = \Modules\Integrations\Entities\WialonVehicle::all();

    foreach ($vehicles as $vehicle) {

        $car = $vehicle->updateLastPosition();


    }

});

Artisan::command('ex_parse', function () {

    $url = 'https://exkavator.ru/trade/arenda/chelyabinskaya_obl/';
    $data = file_get_contents($url);

    $array = [];
    $dom = new \DOMDocument('1.0', 'UTF-8');
    @$dom->loadHTML(($data));
    $divs = $dom->getElementsByTagName('div');
    foreach ($divs as $div) {

        if ($div->getAttribute('class') !== 'trade-results-items') {
            continue;
        }
        $items = $div->getElementsByTagName('span');
        foreach ($items as $item) {
            if ($item->getAttribute('class') !== 'tooltipster complain tooltipstered') {
                continue;
            }

            $lot = $items->getAttribute('data-lot');
            $user = $items->getAttribute('data-user');
            $array[] = ['lot' => $lot, 'user' => $user];
        }
    }

    dd($array);
});

Artisan::command('after_deploy', function () {
    Artisan::call('migrate', ['--force' => true]);
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('auth:clear-resets');
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('queue:restart');
});
