<?php

namespace Modules\AdminOffice\Http\Controllers;

use App\Directories\Unit;
use App\Finance\TinkoffMerchantAPI;
use App\Machinery;
use App\Machines\Brand;
use App\Machines\Type;
use App\Role;
use App\Support\Country;
use App\Support\FederalDistrict;
use App\Support\Region;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Modules\AdminOffice\Entities\DownloadLink;
use Modules\AdminOffice\Entities\User\AccessBlock;
use Modules\ContractorOffice\Entities\System\Tariff;
use Modules\Dispatcher\Entities\Customer;
use Modules\Orders\Entities\Order;
use Modules\RestApi\Entities\Content\Tag;
use Modules\RestApi\Entities\Domain;

class AdminOfficeController extends Controller
{

    function generateLinkHash()
    {
        $hash = str_random(6);

        DownloadLink::create(['link_hash' => $hash, 'user_id' => Auth::id()]);

        return response()->json(['hash' => $hash]);
    }

    function getRegions(Request $request)
    {

        $regions = Region::with('cities');

        if ($request->filled('country')) {

            $regions->whereCountry($request->country);
        }

        if ($request->filled('country_id')) {

            $regions->whereCountryId($request->country_id);
        }

        return $regions->get();
    }

    function getCountries()
    {
        return Country::all();
    }

    function getRegionals()
    {
        $regionals = User::regionalRepresentative()->get();

        return $regionals->map(function ($user) {

            $user->email = $user->rp_name;
            return $user;
        });
    }

    function getEditUserFormData()
    {
        $roles = \Spatie\Permission\Models\Role::all();

        $domains = $this->getDomains();
        $countries = Country::with(['regions' => function ($q) {
            $q->with('cities');
        }])->get();
        // $regionals = convert_from_latin1_to_utf8_recursively($this->getRegionals()->toArray());

        return \response()->json([
            'roles' => $roles,
            'domains' => $domains,
            'countries' => $countries,
            'regionals' => $this->getRegionals()
        ])->setExpires(now()->addMinutes(15));
    }


    function getSearchUserInitialData(Request $request)
    {
        $roles = \Spatie\Permission\Models\Role::all();
        $regions = $this->getRegions($request);
        $regionals = $this->getRegionals();

        return \response()->json(['regions' => $regions, 'roles' => $roles, 'regionals' => $regionals]);
    }

    function getSearchVehiclesInitialData(Request $request)
    {
        $types = Type::all();
        $regions = $this->getRegions($request);
        $brands = Brand::all();
        $units = Unit::all();

        return \response()->json(['regions' => $regions, 'types' => $types, 'brands' => $brands, 'units' => $units]);
    }

    function getEditVehiclesInitialData()
    {
        $types = Type::all();
        $regions = Region::with('cities')->forDomain()->get();
        $brands = Brand::all();

        return \response()->json(['regions' => $regions, 'types' => $types, 'brands' => $brands, 'telematics' => Machinery::getTelematics()]);
    }


    function getSearchOrderInitialData(Request $request)
    {
        $types = Type::all();
        $regions = $this->getRegions($request);

        return \response()->json(['regions' => $regions, 'types' => $types]);
    }

    function getSearchPaymentsInitialData()
    {
        $statuses = TinkoffMerchantAPI::STATUS_LANG;
        $result = [];
        foreach ($statuses as $key => $val) {
            $result[] = [
                'alias' => $key,
                'value' => $val
            ];
        }

        return \response()->json(['statuses' => $result]);
    }


    function uploadImage(Request $request)
    {
        //   print_r($request->file('files')[0]->getMimeType());die();
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'mimetypes:image/jpeg,image/png,image/jpg,image/gif,image/svg,image/svg+xml|max:2048',
        ]);

        $scans = [];
        $files = $request->file('files');

        foreach ($files as $file) {

            $extension = $file->getClientOriginalExtension();

            $fileName = str_random(5) . "-" . date('his') . "-" . str_random(3) . "." . $extension;

            $tmp_path = Storage::disk()->putFile($fileName, $file);

            $tmp_url = Storage::disk()->url($tmp_path);

            $url = "uploads/images/{$fileName}";

           // if ($extension !== 'svg') {
           //     $image = Image::make($tmp_url);
//
           //     if (toBool($request->input('setWatermark'))) {
           //         $logo_path = \config('request_domain')->alias === 'ru'
           //             ? 'img/logos/logo-tb-eng-g-200.png' : 'img/logos/logo-kinsok.png';
//
           //         $image->insert(public_path($logo_path, 'bottom-right', 10, 10));
           //     }
//
           //     Storage::disk()->put($url, $image->encode(null, 50));
//
           //     Storage::disk()->delete($tmp_path);
//
           // } else {
                Storage::disk()->move($tmp_path, $url);
           // }

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

    function getDomains()
    {
        return Domain::all();
    }

    function indexData(Request $request)
    {

        $users = User::query()
            ->forDomain()
            ->whereDate('created_at', '>=', now()->startOfWeek())
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('D');

            })->map(function ($item) {
                return count($item);
            });

        $users->put('max', $users->max());

        $vehicles = Machinery::query()
            ->forDomain()
            ->whereDate('created_at', '>=', now()->startOfYear())
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('m');

            })->map(function ($item) {
                return count($item);
            });

        $vehicles->put('max', $vehicles->max());

        $orders = Order::query()
            ->forDomain()
            ->whereDate('created_at', '>=', now()->startOfYear())
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('m');

            })
            ->map(function ($item) {
                return count($item);
            });

        $orders->put('max', $orders->max());

        $admins = User::query()
        /*    ->whereHas('roles', function ($q) {
                $q->whereIn('alias', ['admin', 'content_admin']);
            })*/
            ->orWhere('is_regional_representative', 1)->get();


        $categories = Type::query()
            ->whereHas('machines', function ($q) {
                $q->whereHas('region', function ($q) {
                    $q->forDomain();
                });
            })->withCount(['machines' => function ($q) {
                return $q->forDomain();
            }])->get()->each->localization();

        $regions = Region::query()
            ->whereHas('machines')
            ->forDomain()
            ->withCount(['users' => function ($q) {
                $q->whereDoesntHave('machines')
                    ->orWhereHas('roles', function ($q) {
                        $q->whereAlias('customer');
                    });
            }])->get();

        foreach ($categories as $category) {

            $regions = $regions->map(function ($region) use ($category) {

                $count = $category->machines()->whereRegionId($region->id)->count();
                if ($count) {
                    $region['category_' . $category->id] = $count;
                }

                return $region;

            });
        }

        $stats_data = [
            'categories' => $categories,
            'regions' => $regions,
        ];

        $last_active = User::orderBy('last_activity', 'desc')->take(5)->get();

        return response()->json([
            'sms_balance' => (new \App\Service\Sms())->get_balance(),
            'users' => $users,
            'vehicles' => $vehicles,
            'orders' => $orders,
            'last_active' => $last_active,
            'admins' => $admins,
            'stats_data' => $stats_data,
        ]);
    }

    function getContentData()
    {
        $tags = Tag::all()->pluck('name');
        $domains = Domain::all();
        $federal_districts = FederalDistrict::forDomain()->get();

        return response()->json(compact('tags', 'domains', 'federal_districts'));
    }

    function getAccessBlocks()
    {
        return AccessBlock::all();
    }

    function ckUpload(Request $request)
    {
        $request->validate([
            'upload' => 'image|max:2048'
        ]);

        if ($request->hasFile('upload')) {

            $file = $request->file('upload');

            $fileName = str_random(5) . "-" . date('his') . "-" . str_random(3) . "." . $file->getClientOriginalExtension();

            $tmp_path = Storage::disk()->putFile($fileName, $file);

            $tmp_url = Storage::disk()->url($tmp_path);

            $url = "uploads/content/{$fileName}";

            if ($file->getClientOriginalExtension() != 'svg') {
                $image = Image::make($tmp_url);

                Storage::disk()->put($url, $image->encode(null, 50));

                Storage::disk()->delete($tmp_path);

            } else {
                Storage::disk()->move($tmp_path, $url);
            }

            return response()->json([
                "uploaded" => 1,
                "fileName" => $fileName,
                "url" => Storage::disk()->url($url)
            ]);
        }
    }

    function getPhoneInfo(Request $request)
    {
        $phone = trimPhone($request->phone);

        $user = User::query()->where('phone', $phone)->first() ?: Customer::query()->where('phone', $phone)->first();

        return $user ? response()->json(
            [
                'user' => $user,
                'type' => $user instanceof User ? 'user' : 'client'
            ]
        )
            : response()->json([], 404);

    }

    function getCategoriesTariffs()
    {
        return Tariff::query()->where('type', '!=', Tariff::TIME_CALCULATION)->get();
    }

}
