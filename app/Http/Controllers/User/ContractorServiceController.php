<?php

namespace App\Http\Controllers\User;

use App\City;
use App\Directories\ServiceCategory;
use App\Directories\ServiceOptionalField;
use App\Marketing\Service;
use App\Service\SubmitFormOnService;
use App\Service\Subscription;
use App\Support\Region;
use App\User;
use App\User\Contractor\ContractorService;
use App\User\Contractor\SeoServices;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ContractorServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->filled('get_options')) {
            $id = $request->type_id;

            $options = ServiceOptionalField::whereServiceCategoryId($id)->get();

            return response()->json(['data' => view('user.services.optional_fields', compact('options', 'id'))->render()]);
        }
        $regions = Region::whereCountry('russia')->get();
        $categories = ServiceCategory::all();
        return view('user.services.index', ['services' => Auth::user()->services, 'regions' => $regions, 'categories' => $categories]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('user.services.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'service_category_id' => 'required|exists:service_categories,id',
                'region_id' => 'required|exists:regions,id',
                'city_id' => 'required|exists:cities,id',
                'name' => 'required|string',
                'photo' => 'required|string',
                'text' => 'required|string',
                'size' => 'required|string',
                'sum' => 'required|numeric',
            ],
            [
                'service_category_id.required' => 'Выберите категорию.',
                'region_id.required' => 'Выберите регион.',
                'region_id.exists' => 'Выберите регион.',
                'city_id.required' => 'Выберите город.',
                'city_id.exists' => 'Выберите город.',
                'name.required' => 'Заполните наименование',
                'photo.required' => 'Загрузите фото.',
                'text.required' => 'Описание обязательно к заполнению.',
                'size.required' => 'Минимальный объём заказа обязателен к заполнению.',
                'sum.required' => 'Укажите стоимость минимального заказа.',
                'sum.numeric' => 'Некорректное число.',
            ]);

        $errors = $validator->errors()->getMessages();

        if ($errors) return response()->json($errors, 419);


        DB::beginTransaction();

        $service = ContractorService::create([
            'user_id' => Auth::id(),
            'service_category_id' => $request->service_category_id,
            'region_id' => $request->region_id,
            'city_id' => $request->city_id,
            'name' => $request->name,
            'photo' => $request->photo,
            'text' => $request->text,
            'size' => $request->size,
            'sum' => $request->sum

        ]);
        $fields = [];
        foreach ($request->all() as $key => $value) {
            $check = explode('_cat' . $service->service_category_id . '_', $key);

            if (isset($check[0], $check[1]) && $check[0] === 'option') {
                if ($value) {
                    $fields[$check[1]] = ['value' => $value];
                }

            }
        }
        $service->optionalAttributes()->sync($fields);
        DB::commit();

        return response()->json(['message' => 'Услуга добавлена']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $service = ContractorService::currentUser()->findOrFail($id);
        $options = ServiceOptionalField::whereServiceCategoryId($service->service->id)->get();

        return view('user.services.edit', ['service' => $service, 'options' => $options]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $service = ContractorService::currentUser()->findOrFail($id);
        $validator = Validator::make($request->all(),
            [
                'service_category_id' => 'required|exists:service_categories,id',
                'region_id' => 'required|exists:regions,id',
                'city_id' => 'required|exists:cities,id',
                'name' => 'required|string',
                'photo' => 'required|string',
                'text' => 'required|string',
                'size' => 'required|string',
                'sum' => 'required|numeric',
            ],
            [
                'service_category_id.required' => 'Выберите категорию.',
                'region_id.required' => 'Выберите регион.',
                'region_id.exists' => 'Выберите регион.',
                'city_id.required' => 'Выберите город.',
                'city_id.exists' => 'Выберите город.',
                'name.required' => 'Заполните наименование',
                'photo.required' => 'Загрузите фото.',
                'text.required' => 'Описание обязательно к заполнению.',
                'size.required' => 'Минимальный объём заказа обязателен к заполнению.',
                'sum.required' => 'Укажите стоимость минимального заказа.',
                'sum.numeric' => 'Некорректное число.',
            ]);

        $errors = $validator->errors()->getMessages();

        if ($errors) return response()->json($errors, 419);


        DB::beginTransaction();

        $service->fill([
            'service_category_id' => $request->service_category_id,
            'region_id' => $request->region_id,
            'city_id' => $request->city_id,
            'name' => $request->name,
            'photo' => $request->photo,
            'text' => $request->text,
            'size' => $request->size,
            'sum' => $request->sum
        ]);

        $service->save();
        $fields = [];
        foreach ($request->all() as $key => $value) {
            $check = explode('_cat' . $service->service_category_id . '_', $key);

            if (isset($check[0], $check[1]) && $check[0] === 'option') {
                if ($value) {
                    $fields[$check[1]] = ['value' => $value];
                }

            }
        }
        $service->optionalAttributes()->sync($fields);

        DB::commit();

        return response()->json(['message' => 'Услуга обновлена!']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    function directoryMain(Request $request)
    {
        $cats = ServiceCategory::all();

        return response()->json();
    }

    function getDirectoryByCategory(Request $request, $category)
    {
        $category = ServiceCategory::whereAlias($category)->firstOrFail();
        $regions = Region::whereCountry('russia')->with(['cities' => function ($q) use ($request, $category) {
            if ($request->filled('city_id')) {
                $q->whereId($request->city_id);
            }
            $q->whereHas('contractor_services', function ($q) use ($category) {
                $q->whereServiceCategoryId($category->id);
            });
        }])->whereHas('contractor_services', function ($q) use ($category) {
            $q->whereServiceCategoryId($category->id);
        });
        $services = ContractorService::whereServiceCategoryId($category->id);
        if ($request->filled('region')) {
            $regions->whereId($request->region);
            $services->whereRegionId($request->region);
        }
        if ($request->filled('city_id')) {
            $services->whereCityId($request->city_id);
        }
        $regions = $regions->get();
        $services = $services->get();


        return $request->ajax()
            ? response()->json(['table' => view('special_categories.table', compact('regions', 'category', 'services'))->render()])
            : view('user.services.directory.category', compact('category', 'regions', 'services'));
    }

    function directoryMainResult($category, $city, $region)
    {
        $category = ServiceCategory::whereAlias($category)->firstOrFail();
        $region = Region::whereAlias($region)->firstOrFail();
        $city = City::whereAlias($city)->whereRegionId($region->id)->firstOrFail();


        $users = User::whereHas('services', function ($q) use ($city, $category) {
            $q->whereCityId($city->id)->whereServiceCategoryId($category->id);
        })->get();
        $services = ContractorService::whereServiceCategoryId($category->id)->whereCityId($city->id)->whereRegionId($region->id)->paginate(10);
        return view('user.services.directory.result', compact('users', 'category', 'city', 'region', 'services'));
    }

    function showRent($category, $city, $region, $alias)
    {
        $category = ServiceCategory::whereAlias($category)->firstOrFail();
        $region = Region::whereAlias($region)->firstOrFail();
        $city = City::whereAlias($city)->whereRegionId($region->id)->firstOrFail();
        $service = ContractorService::whereCityId($city->id)->whereServiceCategoryId($category->id)->whereAlias($alias)->firstOrFail();
        $time_type = [
            [
                'id' => 1,
                'name' => 'Час',
            ],
            [
                'id' => 2,
                'name' => 'Смена',
            ],
            [
                'id' => 3,
                'name' => 'День',
            ],
            [
                'id' => 4,
                'name' => 'Неделя',
            ],
            [
                'id' => 5,
                'name' => 'Месяц',
            ],
        ];
        return view('user.services.directory.rent_page', compact('service', 'time_type'));
    }

    function showPublicPage(Request $request, $alias = null)
    {
        if (!is_null($alias)) {
            $user = User::with(['services' => function ($q) use ($request) {
                if ($request->filled('type_id')) {
                    $q->whereServiceCategoryId($request->type_id);
                }
                if ($request->filled('region')) {
                    $q->whereRegionId($request->region);
                }
                if ($request->filled('city_id')) {
                    $q->whereCityId($request->city_id);
                }
            }])
                // ->whereContractorAliasEnable(1)
                ->whereContractorAlias($alias)->firstOrFail();
            $services = $user->services->sortByDesc('created_at');
        } else {
            $user = null;
            $services = ContractorService::with('user');
            if ($request->filled('type_id')) {
                $services->whereServiceCategoryId($request->type_id);
            }
            if ($request->filled('region')) {
                $services->whereRegionId($request->region);
            }
            if ($request->filled('city_id')) {
                $services->whereCityId($request->city_id);
            }

            $services = $services->orderBy('created_at', 'desc')->paginate(20);
        }


        $regions = Region::whereCountry('russia')->whereHas('contractor_services', function ($q) use ($user) {
            if($user){
                $q->whereUserId($user->id);
            }

        })->get();

        $types = ServiceCategory::whereHas('contractor_services', function ($q) use ($user) {

            if($user){
                $q->whereUserId($user->id);
            }

        })->get();

        $initial_type = $request->filled('type_id') ? ServiceCategory::find($request->type_id) : '';
        $initial_region = ($request->filled('region') ? Region::find($request->region) : '');
        $checked_city_source = ($request->filled('city_id') ? City::find($request->city_id) : '');


        $time_type = [
            [
                'id' => 1,
                'name' => 'Час',
            ],
            [
                'id' => 2,
                'name' => 'Смена',
            ],
            [
                'id' => 3,
                'name' => 'День',
            ],
            [
                'id' => 4,
                'name' => 'Неделя',
            ],
            [
                'id' => 5,
                'name' => 'Месяц',
            ],
        ];
        return view('user.services.public_page', compact('services', 'time_type', 'user', 'types', 'regions', 'initial_region', 'initial_type', 'checked_city_source'));
    }

    function makeRent(Request $request)
    {
        $service = new SubmitFormOnService($request);
        $errors = $service->acceptSimpleForm()->getErrors();
        if ($errors) return response()->json($errors, 419);



        if($service->created_user){
                 (new Subscription())->newUserFromForm($service->created_user, $service->created_user_password);
        }

          (new Subscription())->newSubmitSimpleForm($service->getSubmitForm(), $service->created_user ? true : false);
        return response()->json(['message' => 'Заявка отправлена!']);
    }
}
