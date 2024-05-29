<?php

namespace Modules\RestApi\Http\Controllers;

use App\City;
use App\Helpers\RequestHelper;
use App\Machinery;
use App\Machines\Brand;
use App\Machines\OptionalAttribute;
use App\Machines\Type;
use App\Machines\WorkHour;
use App\Option;
use App\Service\RequestBranch;
use App\Support\Region;
use App\User;
use Config;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Modules\AdminOffice\Entities\RpContact;
use Modules\AdminOffice\Entities\SiteFeedback;
use Modules\CompanyOffice\Entities\Company;
use Modules\CompanyOffice\Transformers\CompanyInfoResource;
use Modules\ContractorOffice\Entities\System\TariffUnitCompare;
use Modules\ContractorOffice\Entities\Vehicle\MachineryBase;
use Modules\Orders\Entities\Order;
use Modules\RestApi\Entities\Domain;
use Modules\RestApi\Transformers\VehicleSearch;

class HelpersController extends Controller
{


    function getConfig()
    {

        $domains = Domain::all();
        $domains->each->setAppends(['country']);
        return response()->json([
            'currencies' => config('app.currencies'),
            'domains' => $domains,
            'system_commission' => Option::get('system_commission') / 100,
        ]);
    }

    function getBrands(Request $request, $category_id = null)
    {
        $brands = Brand::query();

        if ($request->has('withMachines') && $category_id) {

            $brands->whereHas('machines', function ($q) use ($category_id) {
                $q->whereType($category_id);
            });

        }
        if ($request->filled('search_word') && Str::length($request->input('search_word')) > 3) {
            $brands->where('name', 'like', "%{$request->search_word}%");
        }
        if ($request->filled('category_id')) {
            $brands->whereHas('machineryModels', function (Builder $q) use ($request) {
                $q->where('category_id', $request->input('category_id'));
            });
        }
        return $brands->orderBy('name')->get();
    }

    function getRegions(Request $request, $category_id = null)
    {
        $regions = Region::query()->forDomain();

        //   $regions->localization();

        if ($request->has('withMachines') && $category_id) {

            $regions->whereHas('machines', function ($q) use ($category_id) {
                $q->whereType($category_id);
            });

        }

        if ($request->has('withCities')) {

            $regions->with([
                'cities' => function ($q) use ($category_id, $request) {

                    if ($request->has('withMachines') && $category_id) {

                        $q->whereHas('machines', function ($q) use ($category_id) {
                            $q->whereType($category_id);
                        });

                    }
                }
            ]);
        }

        return $regions->orderBy('name')->get();
    }

    function getCities(Request $request, $region_id = null)
    {
        $cities = City::query();

        if ($region_id) {
            $cities->where('region_id', $region_id);
        }
        if ($request->has('withMachines')) {

            $cities->whereHas('machines', function ($q) use ($request) {
                if ($request->filled('category_id')) {
                    $q->whereType($request->category_id);
                }
            });

        }
        return $cities->get();
    }

    function searchCity(Request $request, $id = null)
    {
        if ($id) {
            return City::with('region')->findOrFail($id);
        }
        $errors = \Validator::make($request->all(), ['search_word' => 'required|string|min:2|max:30'])->errors()->all();
        if ($errors) {
            return [];
        }

        $cities = City::with('region')
            ->where('name', 'like', "%{$request->search_word}%")
            ->whereHas('region', function ($q) {
                $q->whereIn('country_id', RequestHelper::requestDomain()->countries->pluck('id')->toArray());
            })
            ->orderBy('name');

        if ($request->filled('region_id')) {
            $cities->where('region_id', $request->input('region_id'));
        }

        return $cities->get();
    }

    function editVehicleHelpers(Request $request)
    {
        return response()->json([
            'categories' => $this->getCategories($request),
            'bases' => MachineryBase::query()->forBranch()->get(),
            'regions' => $this->getRegions($request),
            'brands' => $this->getBrands($request),
            'telematics' => Machinery::getTelematics(),
            'units_compares' => TariffUnitCompare::forBranch()->get()
        ]);
    }

    function getCategories(Request $request, $id = null)
    {
        $categories = Type::query();

        if ($request->has('withMachines')) {

            $categories->whereHas('machines', function ($q) {
                $q->forDomain()->rented();
            });

        }
        if ($id) {
            return $categories->findOrFail($id);
        }

        if ($request->filled('forCompany')) {
            $categories->whereHas('machines', function ($q) use ($request) {
                $q->forCompany($request->input('forCompany'));
            });
        }
        if ($request->filled('forBrand')) {
            $categories->whereHas('machineryModels', function (Builder $q) use ($request) {
                $q->where('brand_id', $request->input('forBrand'));
            });
        }
        $categories = $categories->orderBy('name')->get();


        return Type::setLocaleNames($categories);
    }

    function checkInitialFail(Request $request)
    {
        $type = Type::query()->where('alias', $request->input('category_alias'));

        if (\App::getLocale() !== 'ru') {
            $type->orWhere('eng_alias', $request->input('category_alias'));
        }

        $type->firstOrFail();

        if ($request->filled('region_alias')) {

            $region = Region::query()->where('alias', $request->input('region_alias'))->firstOrFail();

            if ($request->filled('city_alias')) {

                $region->cities()->whereAlias($request->input('city_alias'))->firstOrFail();
            }
        }


    }

    function initialData(Request $request)
    {
        $this->checkInitialFail($request);
        $next = now()->addDays(2)->startOfDay()->format('Y-m-d');
        $categories = Type::query();

        if (app(RequestBranch::class)->company) {
            $categories->whereHas('machines', function ($q) {
                $q->forCompany();
                if (app(RequestBranch::class)->companyBranch) {
                    $q->forBranch();
                }
            });
        }
        $categories = $categories->orderBy('name')->get();

        $regions = Region::forDomain(RequestHelper::requestDomain()->alias)->with('cities');

        if (app(RequestBranch::class)->company) {
            $regions->whereHas('machines', function ($q) {
                $q->forCompany();
                if (app(RequestBranch::class)->companyBranch) {
                    $q->forBranch();
                }
            });
        }

        $regions = $regions->orderBy('name')->get();

        $brands = Cache::remember('brands', 3600, function () {
            return Brand::query()->orderBy('name')->get();
        });


        return response()->json([
            'categories' => Type::setLocaleNames($categories),
            'regions' => $regions,
            'brands' => $brands,
            'min_date' => $next,
            'search_url' => route('search_vehicles')
        ]);
    }


    private function prepareFastOrder($vehicle)
    {
        $item = clone $vehicle;
        $prepare_order = [
            'date_from' => now()->addDay()->format('d.m.Y'),
            //'time_from' => '08:00',
            'date_to' => now()->addDay()->format('d.m.Y'),
            //'time_to' => '08:00',
            'days_count' => 1,
            'full_cost' => $vehicle->sum_day_format,
            'day_cost' => $vehicle->sum_day_format,
            'order_vehicles' => [VehicleSearch::make($item)],
            'vehicles_count' => 1,

        ];

        return $prepare_order;
    }

    function popularOffers(Request $request)
    {
        $random = Machinery::whereNotIn('photo', ['null', json_encode([])])->whereHas('_type', function ($q) {
            $q->whereType('machine');
        })->checkAvailable(now()->addDay(), now()->addDay(2))
            ->with('_type', 'city', 'region', 'work_hours', 'optional_attributes')
            ->forDomain()
            ->inRandomOrder()
            ->paginate($request->input('count', 3));

        return VehicleSearch::collection($random);
    }


    function initialIndexData(Request $request)
    {
        $next = now()->addDays(2)->startOfDay()->format('Y-m-d');

        $counter = Cache::remember('counter', 3600, function () {
            return [
                'total_users' => User::query()->count(),
                'total_orders' => Order::query()->count(),
                'total_vehicles' => Machinery::query()->count(),
            ];
        });
        $reviews = SiteFeedback::query()->country(\config('in_mode') ? 'australia' : 'russia')->get();
        return \response()->json([
            'minDate' => $next,
            'minTime' => '08:00',
            'counter' => $counter,
            'reviews' => $reviews,
            'categories' => $this->getCategories($request),
        ]);
    }


    function initialProfileData(Request $request)
    {

        $regions = Region::query()->with('cities')
            ->where('country_id', Auth::user()->country_id)->get();

        return response()->json(['regions' => $regions])->setExpires(now()->addMinutes(15));
    }


    function getContacts()
    {
        $options = \Config::get('global_options');

        $contacts = RpContact::query()
            ->country(RequestHelper::requestDomain()->alias !== 'ru' ? 'australia' : 'russia')
            ->get();

        $contacts->each->setHidden(['phone', 'email']);

        $company = $options->where('key',
            'company_requisite_'.(RequestHelper::requestDomain()->alias !== 'ru' ? 'australia' : 'russia'))->first();

        return \response()->json([
            'contacts' => $contacts,
            'company' => json_decode($company->value),
        ]);
    }

    function uploadImage(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|image',
        ]);

        $scans = [];

        $tmp = config('app.upload_tmp_dir');

        $files = $request->file('files');
        foreach ($files as $file) {

            $extension = $file->getClientOriginalExtension();

            $fileName = str_random(5)."-".date('his')."-".str_random(3).".".$extension;

            $tmp_path = Storage::disk()->putFile($fileName, $file);

            $tmp_url = file_get_contents(Storage::disk()->url($tmp_path));

          //  $image = Image::make($tmp_url);

            Storage::disk()->delete($tmp_path);

          // if (!$request->filled('nowatermark')) {
          //     $logo_path = RequestHelper::requestDomain()->alias === 'ru'
          //         ? 'img/logos/logo-tb-eng-g-200.png' : 'img/logos/logo-kinsok.png';
          //     $image->insert(public_path($logo_path, 'bottom-right', 10, 10));
          // }


            $url = "{$tmp}/{$fileName}";

            Storage::disk()->put($url, $tmp_url/*$image->encode(null, 50)*/);


            $scans[] = [
                'url' => Storage::disk()->url($url),
                'value' => $url,
            ];
            if ($request->filled('single')) {
                $scans = $scans[0];
            }
        }
        return response()->json($scans, 200);
    }

    function uploadDocuments(Request $request, $rule = 'required')
    {

        $request->validate([
            'files' => 'required|array',
            'files.*' => $rule,
        ]);

        $scans = [];
        $files = $request->file('files');

        $tmp_dir = config('app.upload_tmp_dir');

        foreach ($files as $file) {
            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $name = str_replace(' ', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $time = time();
            $fileName = "$name-$time.$extension";

            $tmp_path = Storage::disk()->putFileAs($tmp_dir, $file, $fileName);

            $tmp_url = Storage::disk()->url($tmp_path);

            $scans[] = [
                'url' => $tmp_url,
                'value' => $tmp_path,
            ];
            if ($request->filled('single')) {
                $scans = $scans[0];
            }
        }
        return response()->json($scans, 200);
    }

    function uploadContracts(Request $request)
    {
        return $this->uploadDocuments($request, 'required|mimes:doc,docx');
    }

    function fastOrder(Request $request)
    {
        $vehicle = Machinery::with('work_hours', 'optional_attributes')->whereAlias($request->alias)->firstOrFail();

        $vehicle->work_hours = $vehicle->work_hours->map(function ($item) {
            return WorkHour::apiMap($item);
        });

        return $this->prepareFastOrder($vehicle);
    }


    function getAnalytic()
    {
        $global_options = config('global_options');

        $head = $global_options->where('key', 'analytics_head')->first()->value;

        $body = $global_options->where('key', 'analytics_body')->first()->value;

        return response()->json([
            'head' => $head,
            'body' => $body,
        ]);
    }

    function getCatalog(Request $request)
    {
        $categories = $this->getCategories($request);

        return response()->json(['data' => $categories]);
    }

    function getOptionalAttributes($category_id)
    {
        return OptionalAttribute::where('type_id', $category_id)->get();
    }

    function uploadAvatar(Request $request)
    {
        $errors = Validator::make($request->all(), [
            'image' => 'required|image|max:1024'
        ])
            ->errors()
            ->getMessages();

        if ($errors) {
            return response()->json($errors, 419);
        }
        $user = \Auth::user();

        Storage::disk()->deleteDirectory('/avatars/'.$user->id);
        $file = $request->file('image');
        $image = Image::make($file);
        $saved_avatar = Storage::disk()->put('avatars/'.auth()->user()->id."/{$file->hashName()}",
            $image->encode(null, 50));

        return response()->json(['saved_avatar' => $saved_avatar], 200);
    }


    function getSitemapLinks(Request $request)
    {

        Domain::whereAlias($request->header('domain'))->firstOrFail();

        $cities = City::query()
            ->whereHas('machines')
            ->with([
                'machines' => function ($q) {
                    $q->with('_type');
                    $q->distinct('type');
                }, 'region'
            ])
            ->forDomain()
            ->get();

        $result = [];

        foreach ($cities as $city) {

            foreach ($city->machines->unique('type') as $machine) {

                $link = route('directory_main_result', [$machine->_type->alias, $city->region->alias, $city->alias],
                    false);
                $result[] = [
                    'region' => $city->region->name,
                    'city' => $city->name,
                    'category' => $machine->_type->name_style,
                    'link' => $link,
                ];
            }
        }

        return response()->json($result);

    }

    function checkCompany(Request $request, $alias)
    {

        $company = Company::query()->where('alias', $alias)->with('branches', 'domain');

        if ($request->filled('branch_id')) {
            $company->whereHas('branches', function ($q) use ($request) {
                $q->where('company_branches.id', $request->input('branch_id'));
            });
        }
        $company = $company->firstOrFail();

        return CompanyInfoResource::make($company);
    }


    function demoRequest(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'phone' => 'required|numeric|digits:'.RequestHelper::requestDomain()->options['phone_digits'],
            'email' => 'required|email',
            'comment' => 'nullable|string|max:255',
        ]);

        $message = (new MailMessage())
            ->subject('Запрос демо-доступа к TRANSBAZA.CRM')
            ->line("Ф.И.О {$request->input('name')}")
            ->line("Email: {$request->input('email')}")
            ->line("Телефон: +{$request->input('phone')}")
            ->line("Наименование компании: {$request->input('company_name')}")
            ->line("Комментарий: {$request->input('comment')}");

         Mail::to('ruslan@trans-baza.ru')->queue(new \App\Mail\Subscription($message, 'Запрос демо-доступа к TRANSBAZA.CRM'));


        try {
            $token = config('services.jira.token');
            $config = [
                'jiraHost' => 'https://trans-baza.atlassian.net',
                'jiraUser' => 'ruslan@trans-baza.ru',
                'jiraPassword' => $token,
            ];
            $confService = new \JiraRestApi\Configuration\ArrayConfiguration($config);
            $issueService = new \JiraRestApi\Issue\IssueService($confService);
            $issueField = new \JiraRestApi\Issue\IssueField();

            $issueField->setProjectKey("SALE")
                ->setIssueType("Bug")
                ->setSummary("Ф.И.О {$request->input('name')} Email: {$request->input('email')} Телефон: +{$request->input('phone')} Комментарий: {$request->input('comment')}")
                ->setDescription("Новый клиент {$request->input('company_name')}");

            $ret = $issueService->create($issueField);

        } catch (\Exception $exception) {
            logger($exception->getMessage());
        }

        return response()->json();
    }

}
