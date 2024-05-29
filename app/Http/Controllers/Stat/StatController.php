<?php

namespace App\Http\Controllers\Stat;

use App\City;
use App\Content\StaticContent;
use App\Directories\ServiceCategory;
use App\Machinery;
use App\Machines\Type;
use App\Role;
use App\Seo\RequestContractor;
use App\Seo\RequestDeletePhone;
use App\Seo\RequestDeleteServicePhone;
use App\Service\Stat;
use App\Support\Country;
use App\Support\Region;
use App\Support\SeoContent;
use App\Support\SeoServiceDirectory;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\RestApi\Entities\Domain;

class StatController extends Controller
{
    function index()
    {


        return view('stat.index');
    }

    function getCities($number)
    {
        $cities = City::where('region_id', $number)->get();
        return response()->json(['cities' => $cities], 200);
    }

    function getRegions($number)
    {
        $city = City::findOrFail($number);

        return response()->json(['region_name' => $city->region->name, 'city_name' => $city->name]);
    }

    function getStats(Request $request)
    {
        $type = $request->type;
        $id = $request->id;
        $category = $request->category;
        $categories = Type::whereHas('stats')->with(['stats' => function ($q) use ($type, $id) {
            $q->where(($type === 'region') ? 'region_id' : 'city_id', $id);
            $q->selectRaw('MAX(max_cost) AS max, MIN(min_cost) AS min, AVG(average) AS aver, category_id, sum(total) as count');

            if ($type === 'region') {
                $q->groupBy('category_id', 'region_id');
            } else {
                $q->groupBy('city_id');
            }

            return $q;
        }]);

        if ($category) {
            $categories->whereId($request->category);
        }
        $categories = $categories->get();
        //dd($categories);
        return response()->view('stat.table', compact('categories', 'type', 'category'));
    }

    function getRegionStats(Request $request)
    {

        $category = $request->category;
        $categories = Region::whereHas('stats', function ($q) use ($category) {
            $q->where('category_id', $category);
        })->with(['stats' => function ($q) use ($category) {
            $q->where('category_id', $category);
            $q->select('category_id', 'region_id');
            $q->selectRaw('MAX(max_cost) AS max, MIN(min_cost) AS min, AVG(average) AS aver, sum(total) as count');
            $q->groupBy('region_id');

            return $q;
        }])->get();


        //dd($categories->toArray());
        return response()->view('stat.table_region', compact('categories'));
    }

    function moreUserInfo(Request $request)
    {
        $errors = Validator::make($request->all(), [
            'role' => 'required',
        ])->errors()->all();
        if ($errors) {
            return 'Ошибка ' . implode('.', $errors);
        }
        $users = User::query();
        $curr_role = Role::whereAlias($request->role)->first();
        switch ($request->role) {
            case 'regional':
                $users->whereIsRegionalRepresentative(1);
                break;
            case 'promoter':
                $users->whereIsPromoter(1);
                break;
            case 'active':
                $users->whereIsBlocked(0)->whereEmailConfirm(1);
                break;
            case 'total':
                break;
            case 'confirm':
                $users->whereEmailConfirm(1);
                break;
            default:
                $users->whereHas('roles', function ($q) use ($request) {
                    $q->whereAlias($request->role);
                });
                break;
        }
        $type = explode('_', $request->type);
        $col = '';

        if (isset($type[1])) {
            $col = $type[1];
            switch ($type[0]) {
                case 'all':
                    $users->withTrashed(1);
                    break;


                /*  case 'confirm':
                      $users->whereEmailConfirm(1);
                      break;
                  case 'not_confirm':
                      $users->whereEmailConfirm(0);
                      break;*/
                case 'blocked':
                    $users->whereIsBlocked(1);
                    break;
                case 'active':
                case 'actual':
                    $users->whereIsBlocked(0);
                    break;
                case 'trashed':
                    $users->withTrashed()->whereNotNull('deleted_at');
                    break;
            }

            switch ($type[1]) {
                case 'ET':
                    $users->whereEmailConfirm(1)->wherePhoneConfirm(1);
                    break;
                case 'EnoT':
                    $users->whereEmailConfirm(1)->wherePhoneConfirm(0);
                    break;
                case 'TnoE':
                    $users->whereEmailConfirm(0)->wherePhoneConfirm(1);
                    break;
                case 'noET':
                    $users->whereEmailConfirm(0)->wherePhoneConfirm(0);
                    break;
            }
        }

        $users = $users->orderBy('last_activity', 'desc')->get();

        return view('stat.more_user_info', compact('request', 'users', 'curr_role', 'col', 'type'));
    }

    function moreInfo(Request $request)
    {
        $errors = Validator::make($request->all(), [
            'type_id' => 'required|in:n_m,m_n,m,n',
        ])->errors()->all();
        if ($errors) {
            return 'Ошибка ' . implode('.', $errors);
        }

        $machines = Machinery::with('city');
        if ($request->city_id !== '0' && $request->filled('city_id')) {
            $machines->whereCityId($request->city_id);
        }
        if ($request->category_id !== '0') {
            $machines->whereType($request->category_id);
        }
        if ($request->filled('region_id') && $request->region_id !== '0') {
            $machines->whereRegionId($request->region_id);
        }
        $machines = $machines->get();

        $users = User::whereHas('machines', function ($q) use ($request) {

            if ($request->category_id !== '0') {
                $q->whereType($request->category_id);
            }
            if ($request->filled('city_id') && $request->city_id !== '0') {
                $q->whereCityId($request->city_id);
            }

            if ($request->filled('region_id') && $request->region_id !== '0') {
                $q->whereRegionId($request->region_id);
            }


        })->withTrashed()->get();

        return view('stat.more_info', compact('request', 'machines', 'users'));
    }

    function getTotal(Request $request)
    {

        if (!Auth::check()) {
            return view('stat.call_to_action');
        }
        $errors = Validator::make($request->all(), [
            'show_type' => 'required|in:n_m,m_n,m,n',
        ])->errors()->all();
        if ($errors) {
            return 'Ошибка ' . implode('.', $errors);
        }
        $machines = \App\Machinery::with('city', '_type');
        if ($request->filled('region_id')) {
            $machines->whereRegionId($request->region_id);
        }
        if ($request->filled('city')) {
            $machines->whereCityId($request->city);
        }
        if ($request->filled('category')) {
            $machines->whereType($request->category);
        }
        $machines = $machines
            ->get()
            ->sortBy(function ($m) {
                return [$m->region->name, $m->city->name];
            })
            ->groupBy(['city.id', '_type.id'])
            ->map(function ($collection, $city_key) {
                $collection->n_count = 0;
                $collection->m_count = 0;

                $collection->map(function ($element, $cat_key) use ($collection, $city_key) {

                    $collection->n_count += $element->n_count = $element->count();
                    $collection->m_count += $element->m_count = \App\User::whereHas('machines', function ($q) use ($city_key, $cat_key) {

                        $q->where('city_id', $city_key)
                            ->where('type', $cat_key);
                    })->withTrashed()->count();

                    return $element;
                });
                return $collection;
            });
        if ($request->show_type === 'm') {
            $machines = $machines->sortByDesc('m_count');
        }
        if ($request->show_type === 'n') {
            $machines = $machines->sortByDesc('n_count');
        }
        // dd($machines);
        $cats = \App\Machines\Type::whereHas('machines', function ($q) use ($request) {
            if ($request->filled('region_id')) {
                $q->whereRegionId($request->region_id);
            }
            if ($request->filled('city')) {
                $q->whereCityId($request->city);
            }
        });
        if ($request->filled('category')) {
            $cats->whereId($request->category);
        }

        $cats = $cats->orderBy('name')->get()->sortByDesc(function ($m) use ($request) {
            if ($request->show_type === 'n') {
                $q = $m->machines();
                if ($request->filled('region_id')) {
                    $q->whereRegionId($request->region_id);
                }
                if ($request->filled('city')) {
                    $q->whereCityId($request->city);
                }
                return $q->get()->count();
            }
            if ($request->show_type === 'm') {

                return User::whereHas('machines', function ($q) use ($request, $m) {
                    if ($request->filled('region_id')) {
                        $q->whereRegionId($request->region_id);
                    }
                    if ($request->filled('city')) {
                        $q->whereCityId($request->city);
                    }
                    $q->whereType($m->id);
                })->withTrashed()->get()->count();
            }


        });

        return view('stat.table_total', compact('machines', 'cats', 'request'));
    }


    function directory(Request $request)
    {
        $cats = Type::query();


        if ($request->filled('type_id')) {
            $cats->whereId($request->type_id);
        }
        $cities = City::with('region');

        if ($request->filled('region')) {
            $cities->whereRegionId($request->region);
        }
        if ($request->filled('city_id')) {
            $cities->whereId($request->city_id);
        }
        $cats = $cats->orderBy('name')->get();
        $cities = $cities->get();

        return
            $request->ajax()
                ? response()->json(['table' => view('special.table', compact('cities', 'cats'))->render()])
                : view('special.index', compact('cities', 'cats'));
    }

    function directoryMain(Request $request)
    {
        $cats = Type::whereHas('machines')->orderBy('name')->get();

        return response()->json();
    }

    function directoryMainCategory(Request $request, $category)
    {
        $category = Type::whereAlias($category)->firstOrFail();
        $regions = Region::with(['cities' => function ($q) use ($request, $category) {
            if ($request->filled('city_id')) {
                $q->whereId($request->city_id);
            }
            $q->whereHas('machines', function ($q) use ($category) {
                $q->whereType($category->id);
            });
        }])->whereHas('machines', function ($q) use ($category) {
            $q->whereType($category->id);
        });
        $machines = Machinery::whereType($category->id);
        if ($request->filled('region')) {
            $current_region = Region::findOrFail($request->region);
            $regions->whereId($request->region);
            $machines->whereRegionId($request->region);
        }
        if ($request->filled('city_id')) {
            $current_city = $current_region->cities()->findOrFail($request->city_id);
            $machines->whereCityId($request->city_id);
        }
        return response('OK');
      /*  $regions = $regions->whereCountry('russia')->get();
        $machines = $machines->paginate(10);

        if (isset($current_region) && !isset($current_city) && $request->ajax()) {
            return response()->json(['link' => route('directory_main_region', [$category->alias, $current_region->alias])]);
        }
        if (isset($current_region, $current_city) && $request->ajax()) {
            return response()->json(['link' => route('directory_main_result', [$category->alias, $current_region->alias, $current_city->alias,])]);
        }


        return $request->ajax()
            ? response()->json(['table' => view('special_categories.table', compact('regions', 'category', 'machines'))->render()])
            : view('special_categories.category', compact('category', 'regions', 'machines'));*/
    }

    function directoryMainRegion(Request $request, $category, $region)
    {
        $category = Type::whereAlias($category)->firstOrFail();
        $region = Region::with(['cities' => function ($q) use ($request, $category) {
            $q->whereHas('machines', function ($q) use ($category) {
                $q->whereType($category->id);
            });
        }])->whereAlias($region)->firstOrFail();
        return response('OK');
      /*  $regions = Region::with(['cities' => function ($q) use ($request, $category) {
            $q->whereHas('machines', function ($q) use ($category) {
                $q->whereType($category->id);
            });
        }])->whereHas('machines', function ($q) use ($category) {
            $q->whereType($category->id);
        })->get();
        $users = User::whereHas('machines', function ($q) use ($region, $category) {
            $q->whereRegionId($region->id)->whereType($category->id);
        })->get();
        $machines = Machinery::whereType($category->id)->whereRegionId($region->id)->paginate(10);
        return view('special_categories.by_region', compact('users', 'category', 'regions', 'region', 'machines'));*/
    }

    function directoryMainResult($category, $region, $city)
    {
        $category = Type::whereAlias($category)->firstOrFail();
        $region = Region::whereAlias($region)->firstOrFail();
        $city = City::whereAlias($city)->whereRegionId($region->id)->firstOrFail();

        return response('OK');
      /*  $users = User::whereHas('machines', function ($q) use ($city, $category) {
            $q->whereCityId($city->id)->whereType($category->id);
        })->get();
        $machines = Machinery::whereType($category->id)->whereCityId($city->id)->whereRegionId($region->id)->paginate(10);
        return view('special_categories.result', compact('users', 'category', 'city', 'region', 'machines'));*/
    }

    function getDirectoryByCategory(Request $request, $category)
    {
        $category = Type::whereAlias($category)->firstOrFail();

        $content = City::with('seo_content')->groupBy('region_id');
        if ($request->filled('region')) {
            $content->whereHas('region', function ($q) use ($request) {
                $q->whereRegionId($request->region);
            });
        }
        $content = $content->get();

        return view('special.category', compact('content', 'category'));
    }

    function getDirectoryByCategoryRegion(Request $request, $category, $region)
    {
        $category = Type::whereAlias($category)->firstOrFail();
        $region = Region::whereAlias($region)->firstOrFail();
        $content = City::whereRegionId($region->id);
        if ($request->filled('city_id')) {
            $content->whereId($request->city_id);
        }
        $content = $content->get();

        return view('special.category_region', compact('content', 'category', 'region'));
    }

    function getInfoByDirectory($category, $region, $city)
    {
        //  dd($category, $city, $region);
        $category = Type::whereAlias($category)->firstOrFail();
        $region = Region::whereAlias($region)->firstOrFail();
        $city = City::whereAlias($city)->whereRegionId($region->id)->firstOrFail();


        $content = SeoContent::where('type_id', $category->id)->whereCityId($city->id)->first();
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
                $data[$a]['name'] = SeoContent::$names[array_rand(SeoContent::$names)];
            }
            $content = SeoContent::create([
                'fields' => json_encode($data),
                'city_id' => $city->id,
                'type_id' => $category->id,
            ]);
        }


        $machine = Machinery::whereCityId($city->id)->whereType($category->id)->first();
        return view('special.result', compact('category', 'city', 'machine', 'region', 'content'));
    }

    function deleteMyPhoneRequest(Request $request)
    {
        if ($request->has('phone')) {
            $request->merge(
                ['phone' => (int)str_replace(
                    [')', '(', ' ', '+', '-'],
                    '',
                    $request->input('phone'))
                ]);
        }
        $errors = Validator::make($request->all(), [
            'region_id' => 'required|integer|exists:regions,id',
            'city_id' => 'required|integer|exists:cities,id',
            'name' => 'required|string',
            'phone' => 'required|numeric|digits:11',
            'comment' => 'string|max:255',
            'g-recaptcha-response' => 'required|captcha',

        ], [
            'city_id.integer' => 'Не выбран город.',
            'city_id.required' => 'Не выбран город.',
            'region.required' => 'Не выбран регион.',
            'region_id.integer' => 'Не выбран регион.',
            'comment.string' => 'Не заполнен комментарий',
            'type_id.integer' => 'Не указана категория техники.',
            'type_id.required' => 'Не указана категория техники.',
            'email.unique' => 'Такой Email уже есть в системе.',
            'email.email' => 'Некорректный Email',
            'phone.required' => 'Некорректный номер телефона.',
            'phone.integer' => 'Некорректный номер телефона.',
            'phone.digits' => 'Некорректный номер телефона.',
            'phone.unique' => 'Такой телефон уже есть в системе.',
        ])
            ->setAttributeNames(['name' => 'Имя'])
            ->errors()
            ->getMessages();
        if ($errors) return response()->json($errors, 419);

        City::whereRegionId($request->region_id)->findOrFail($request->city_id);

        $arr = [
            'city_id' => $request->city_id,
            'region_id' => $request->region_id,
            'phone' => $request->phone,
            'name' => $request->name,
            'comment' => $request->comment,
        ];
        if ($request->filled('delete_service')) {
            RequestDeleteServicePhone::create($arr);
        } else {
            RequestDeletePhone::create($arr);
        }


        return response()->json(['message' => 'Заявка на удаление отправлена.']);
    }

    function newContractorDirectory(Request $request)
    {
        if ($request->has('phone')) {
            $request->merge(
                ['phone' => (int)str_replace(
                    [')', '(', ' ', '+', '-'],
                    '',
                    $request->input('phone'))
                ]);
        }
        $errors = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'region' => 'required|integer|exists:regions,id',
            'city_id' => 'required|integer|exists:cities,id',
            'type_id' => 'required|integer|exists:types,id',
            'name' => 'required|string',
            'phone' => 'required|numeric|digits:11|unique:users,phone',
            'comment' => 'string|max:255',

        ], [
            'city_id.integer' => 'Не выбран город.',
            'city_id.required' => 'Не выбран город.',
            'region.required' => 'Не выбран регион.',
            'region.integer' => 'Не выбран регион.',
            'comment.string' => 'Не заполнен комментарий',
            'type_id.integer' => 'Не указана категория техники.',
            'type_id.required' => 'Не указана категория техники.',
            'email.unique' => 'Такой Email уже есть в системе.',
            'email.email' => 'Некорректный Email',
            'phone.required' => 'Некорректный номер телефона.',
            'phone.integer' => 'Некорректный номер телефона.',
            'phone.min' => 'Некорректный номер телефона.',
            'phone.unique' => 'Такой телефон уже есть в системе.',
        ])
            ->setAttributeNames(['name' => 'Имя'])
            ->errors()
            ->getMessages();
        if ($errors) return response()->json($errors, 419);

        RequestContractor::create([
            'email' => $request->email,
            'city_id' => $request->city_id,
            'type_id' => $request->type_id,
            'phone' => $request->phone,
            'name' => $request->name,
            'comment' => $request->comment,
        ]);

        User::register($request->email, $request->phone);

        return response()->json(['message' => 'Заявка создана.']);
    }

    function directoryServices(Request $request)
    {
        $cats = ServiceCategory::query();


        if ($request->filled('type_id')) {
            $serv = ServiceCategory::findOrFail($request->type_id);
            $cats->whereId($request->type_id);
            $link = route('directory_uslugi_request_category', $serv->alias);
        }
        $cities = City::with('region');

        if ($request->filled('region')) {
            $region = Region::findOrFail($request->region);

            $link = route('directory_uslugi_request_category_region', [$serv->alias, $region->alias]);
            $cities->whereRegionId($request->region);
        }
        if ($request->filled('city_id')) {
            $city = $region->cities()->findOrFail($request->city_id);
            $link = route('directory_uslugi_request', [$serv->alias, $region->alias, $city->alias]);
            $cities->whereId($request->city_id);
        }
        if (isset($link)) {
            return response()->json(['link' => $link]);
        }
        $cats = $cats->orderBy('name')->get();
        $cities = $cities->get();

        $cats_sort = (clone $cats)->groupBy(function ($item, $key) {
            return mb_substr($item->name, 0, 1);     //treats the name string as an array
        });


        return
            $request->ajax()
                ? response()->json(['table' => view('special.table', compact('cities', 'cats'))->render()])
                : view('special_services.index', compact('cities', 'cats', 'cats_sort'));
    }

    function getDirectoryServiceByCategoryRegion(Request $request, $category, $region)
    {
        $category = ServiceCategory::whereAlias($category)->firstOrFail();
        $region = Region::whereAlias($region)->firstOrFail();
        $content = City::with('seo_service_content')->whereRegionId($region->id);
        if ($request->filled('city_id')) {
            $content->whereId($request->city_id);
        }
        $content = $content->get();

        return view('special_services.category_region', compact('content', 'category', 'region'));
    }

    function getDirectoryByService(Request $request, $category)
    {
        $category = ServiceCategory::whereAlias($category)->firstOrFail();

        $content = City::with('seo_service_content')->groupBy('region_id');
        if ($request->filled('region')) {
            $content->whereHas('region', function ($q) use ($request) {
                $q->whereRegionId($request->region);
            });
        }
        $content = $content->get();

        return view('special_services.category', compact('content', 'category'));
    }

    function internationalCheck($country, $locale, $category_alias,  $region = null, $city = null, $alias = null)
    {

      Type::where('eng_alias',$category_alias)->firstOrFail();
      Domain::whereAlias($country)->firstOrFail();
      if($region){
          $region = Region::whereAlias($region)->firstOrFail();
          if($city){
              $city = $region->cities()->whereAlias($city)->firstOrFail();

              if($alias){
                  Machinery::whereCityId($city->id)->whereAlias($alias)->firstOrFail();
              }
          }
      }

    }
}
