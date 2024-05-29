<?php

namespace App\Http\Controllers;

use App\Article;
use App\City;
use App\Directories\ServiceCategory;
use App\Jobs\PushCollectionToSitemapFile;
use App\Machinery;
use App\Machines\Type;
use App\Service\Widget;
use App\Support\Region;
use App\Support\SeoContent;
use App\Support\SeoServiceDirectory;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\RestApi\Entities\Domain;

class HomeController extends Controller
{


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $articles = \App\Article::active()
            ->where('is_static', 0)
            ->where('is_article', 1)
            ->where('only_menu', 0)
            ->orWhere('is_news', 1)
            ->orderBy('created_at', 'desc')
            ->get()
            ->take(9);

        //  $tilda = $this->getTilda();
        $tilda = '';
        $response = response()->view('index', compact('articles', 'tilda'));
        if (request()->server('HTTP_IF_MODIFIED_SINCE')) {
            $article = $articles->first();
            $value = Carbon::parse(request()->server('HTTP_IF_MODIFIED_SINCE'));

            if ($value > $article->updated_at) {
                $response->setNotModified();
            }

        }

        return $response;
    }

    function getTilda()
    {
        $tilda = new \App\Service\Tilda();
        $page = json_decode($tilda->pageExport(4331790), true);
        $html = $page['result']['html'];
        $js = ($page['result']['js']);

        $js_arr = [];
        foreach ($js as $item) {
            if ($item['from'] == 'https://static.tildacdn.com/js/jquery-1.10.2.min.js') {
                continue;
            }
            $js_arr[] = "<script  src='{$item['from']}'></script>";
        }
        $dom = new \DOMDocument();


        $css = ($page['result']['css']);
        $css_arr = [];

        foreach ($css as $item) {
            $css_arr[] = "<link rel='stylesheet' href='{$item['from']}'>";
        }

        if ($dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html)) {
            while (($r = $dom->getElementsByTagName("script")) && $r->length) {
                $js_arr[] = "<script>{$r->item(0)->nodeValue}</script>";
                $r->item(0)->parentNode->removeChild($r->item(0));
            }
            while (($r = $dom->getElementsByTagName("style")) && $r->length) {
                $css_arr[] = "<style>{$r->item(0)->nodeValue}</style>";
                $r->item(0)->parentNode->removeChild($r->item(0));
            }
        }
        $html = $dom->saveHTML();
        $css = implode('', $css_arr);

        $js = implode('', array_reverse($js_arr));
        return ['html' => $html, 'css' => $css, 'js' => $js];
    }

    public function getCities($number)
    {
        $region = \App\Support\Region::findOrFail($number);

        return response()->json(['cities' => $region->cities], 200);
    }

    public function getRegion($number)
    {
        $city = City::findOrFail($number);

        return response()->json(['region_name' => $city->region->name, 'city_name' => $city->name]);
    }

    public function getFilterCities($number)
    {
        $region = \App\Support\Region::findOrFail($number);

        return response()->json(['cities' => $region->cities()->whereHas('machines')->get()], 200);
    }

    function getDepDropCity(Request $request)
    {
        $id = $request->depdrop_all_params['region_id'] ?? 0;

        return response()->json(['output' => City::whereRegionId($id)->get()]);
    }

    public function getFilterRegion($number)
    {
        $city = City::findOrFail($number);

        return response()->json(['region_name' => $city->region->name, 'city_name' => $city->name]);
    }

    function getCountryRegions($id)
    {
        return Region::whereCountryId($id)->get();
    }


    function getWidgetCities(Request $request, $id)
    {
        if ($id == 0) {

            return response()->json([]);
        }
        $region = \App\Support\Region::findOrFail($id);
        $widget = Widget::whereAccessKey($request->key)->firstOrFail();


        return response()->json(['cities' => $region->cities()->whereHas('machines', function ($q) use ($widget, $request) {
            if ($widget->type === Widget::status('my')) {
                $q->where('user_id', $widget->user->id);
            }
            if ($request->filled('type') && $request->type !== '0') {
                $q->whereType($request->type);
            }
        })->get()], 200);
    }

    function getWidgetRegions(Request $request, $type_id)
    {
        $widget = Widget::whereAccessKey($request->key)->firstOrFail();
        Type::findOrFail($type_id);


        $regions = Region::whereCountry('russia')->with(['cities' => function ($q) use ($request, $widget) {

            $q->with('machines')->whereHas('machines', function ($q) use ($request, $widget) {
                if ($request->filled('type') && $request->type !== '0') {
                    $q->whereType($request->type);
                }
                if ($widget->type === Widget::status('my')) {
                    $q->where('user_id', $widget->user->id);
                }
            });

        }])->whereHas('machines', function ($q) use ($widget, $type_id) {
            if ($widget->type === Widget::status('my')) {
                $q->where('user_id', $widget->user->id);
            }
            $q->whereType($type_id);
        })->get();

        return $regions;
    }

    function getWidgetRegionsForMachine(Request $request, $machine_id)
    {
        $widget = Widget::whereAccessKey($request->key)->firstOrFail();
        Machinery::findOrFail($machine_id);


        $regions = Region::whereCountry('russia')->with(['cities' => function ($q) use ($request, $widget) {

            $q->with('machines')->whereHas('machines', function ($q) use ($request, $widget) {
                if ($request->filled('type') && $request->type !== '0') {
                    $q->whereType($request->type);
                }
                if ($widget->type === Widget::status('my')) {
                    $q->where('user_id', $widget->user->id);
                }
            });

        }])->whereHas('machines', function ($q) use ($widget, $machine_id) {
            if ($widget->type === Widget::status('my')) {
                $q->where('user_id', $widget->user->id);
            }
            // $q->whereId($machine_id);
        })->get();

        return $regions;
    }


    function getWidgetTypesRegion(Request $request, $id)
    {

        $widget = Widget::whereAccessKey($request->key)->firstOrFail();

        $types = Type::whereHas('machines', function ($q) use ($widget, $id) {
            if ($widget->type === Widget::status('my')) {
                $q->where('user_id', $widget->user->id);
            }
            $q->whereRegionId($id);
        })->get();

        return $types;
    }


    function getWidgetMachinesRegion(Request $request, $id)
    {

        $widget = Widget::whereAccessKey($request->key)->firstOrFail();

        $machines = Machinery::whereHas('region', function ($q) use ($id) {

            $q->whereRegionId($id);
        });

        if ($widget->type === Widget::status('my')) {
            $machines->where('user_id', $widget->user->id);
        }

        $machines = $machines->get();

        return $machines;
    }

    function getUserCities(Request $request, $id)
    {
        if ($id == 0) {

            return response()->json([]);
        }
        $region = \App\Support\Region::findOrFail($id);
        $user = User::find($request->id);


        return response()->json(['cities' => $region->cities()->whereHas('machines', function ($q) use ($user, $request) {

            if ($user) {
                $q->where('user_id', ($user->id ?? 0));
            }


            if ($request->filled('type') && $request->type !== '0') {
                $q->whereType($request->type);
            }
        })->get()], 200);
    }

    function getTBFeelCities(Request $request, $type, $id)
    {
        if ($id == 0) {

            return response()->json([]);
        }
        $region = \App\Support\Region::findOrFail($id);


        return response()->json(['cities' => $region->cities()->whereHas('machines', function ($q) use ($type) {

            $q->whereType($type);

        })->get()], 200);
    }

    function getUserRegions(Request $request, $type_id)
    {
        $user = User::find($request->id);
        Type::findOrFail($type_id);


        $regions = Region::whereCountry('russia')->with(['cities' => function ($q) use ($request, $user) {

            $q->with('machines')->whereHas('machines', function ($q) use ($request, $user) {
                if ($request->filled('type') && $request->type !== '0') {
                    $q->whereType($request->type);
                }

                $q->where('user_id', ($user->id ?? 0));

            });

        }])->whereHas('machines', function ($q) use ($user, $type_id) {

            if ($user) {
                $q->where('user_id', $user->id);
            }

            $q->whereType($type_id);
        })->get();

        return $regions;
    }

    function getUserServiceRegions(Request $request, $type_id)
    {
        $user = User::find($request->id);
        ServiceCategory::findOrFail($type_id);


        $regions = Region::whereCountry('russia')->with(['cities' => function ($q) use ($request, $user) {

            $q->with('contractor_services')->whereHas('contractor_services', function ($q) use ($request, $user) {
                if ($request->filled('type') && $request->type !== '0') {
                    $q->whereServiceCategoryId($request->type);
                }

                $q->where('user_id', ($user->id ?? 0));

            });

        }])->whereHas('contractor_services', function ($q) use ($user, $type_id) {

            if ($user) {
                $q->where('user_id', $user->id);
            }

            $q->whereServiceCategoryId($type_id);
        })->get();

        return $regions;
    }

    function getUserServiceCities(Request $request, $id)
    {
        if ($id == 0) {

            return response()->json([]);
        }
        $region = \App\Support\Region::findOrFail($id);
        $user = User::find($request->id);


        return response()->json(['cities' => $region->cities()->whereHas('contractor_services', function ($q) use ($user, $request) {

            if ($user) {
                $q->where('user_id', ($user->id ?? 0));
            }


            if ($request->filled('type') && $request->type !== '0') {
                $q->whereServiceCategoryId($request->type);
            }
        })->get()], 200);
    }

    function getUserTypesRegion(Request $request, $id)
    {

        $user = User::find($request->id);

        $types = Type::whereHas('machines', function ($q) use ($user, $id) {

            if ($user) {
                $q->where('user_id', $user->id ?? 0);
            }

            $q->whereRegionId($id);
        })->get();

        return $types;
    }

    function getUserServiceTypesRegion(Request $request, $id)
    {

        $user = User::find($request->id);

        $types = ServiceCategory::whereHas('contractor_services', function ($q) use ($user, $id) {

            if ($user) {
                $q->where('user_id', $user->id ?? 0);
            }

            $q->whereRegionId($id);
        })->get();

        return $types;
    }

    static function siteMapContent()
    {

        $files = Storage::disk()->files('sitemaps');
        $arr = [];
        $i = 0;
        foreach ($files as $k => $file) {
            if(Str::contains($file, 'all')) {
                continue;
            }
            $arr[$i]['file'] = $file;
            $arr[$i]['last_modifed'] = \Carbon\Carbon::createFromTimestamp(Storage::disk()->lastModified($file));
            ++$i;
        }
        $files = $arr;
        return response()->view('sitemaps.sitemap_main', compact('files'));
    }

    static function generateSitemapMain()
    {
        app()->setLocale('ru');

        $cities = City::whereHas('machines')->forDomain('ru')->get();

        $categories = Type::whereHas('machines', function ($q) {
            $q->forDomain('ru');
        })->get();

        $articles = Article::where('is_publish', 1)->forDomain('ru')->whereIn('type', ['article', 'news'])->get();
        $machines = Machinery::whereHas('region', function ($q) {
            $q->whereCountry('russia');
        })->get();

        $data = response()->view('sitemaps.sitemap_site',
            compact(
                'articles',
                'machines',
                'cities',
                'categories'
            ))->getContent();
        Storage::disk()->put("sitemaps/sitemap_all.xml", $data);
        unset($data);
    }

    static function generateSitemapKinosk($domain)
    {
        app()->setLocale($domain->options['default_locale']);


        $cities = City::whereHas('machines')->forDomain($domain->alias)->get();

        $categories = Type::whereHas('machines', function ($q) use ($domain) {
            $q->forDomain($domain->alias);
        })->get();

        $articles = Article::where('is_publish', 1)->forDomain($domain->alias)->whereIn('type', ['article', 'news'])->get();

        $machines = Machinery::forDomain($domain->alias)->get();

        $data = response()->view('sitemaps.sitemap_kinosk',
            compact(
                'articles',
                'machines',
                'cities',
                'domain',
                'categories'
            ))->getContent();
        $data = str_replace(config('app.route_url'), 'kinosk.com', $data);
        Storage::disk()->put("sitemaps/sitemap_kinosk_{$domain->alias}.xml", $data);
        unset($data);
    }


    function getSitemapLnks(Request $request)
    {
        $cities = City::whereHas('region', function ($q) {
            $q->whereCountry('russia');
        })->get();
        $categories = Type::whereHas('machines', function ($q) {
            $q->whereHas('region', function ($q) {
                $q->whereCountry('russia');
            });
        })->get();
        $articles = Article::where('is_publish', 1)->forDomain('ru')->whereIn('type', ['article', 'news'])->get();
        $machines = Machinery::whereHas('region', function ($q) {
            $q->whereCountry('russia');
        })->get();
        $links = [];
        $links[] = route('index_page');
        foreach ($articles as $article) {

            if ($article->type === 'news') {
                $route = route('get_news_article', $article->alias);
            } elseif ($article->type === 'article') {
                $route = route('get_article', $article->alias);
            } else {
                $route = route('article_index', $article->alias);
            }
            $links[] = $route;
        }

        foreach ($machines as $machine) {
            $links[] = $machine->rent_url;
        }

        foreach ($categories as $category) {
            $links[] = route('directory_main_category', $category->alias);
        }

        foreach ($cities as $city) {
            foreach ($city->machines->groupBy('type') as $type => $machines) {
                foreach ($machines as $machine) {
                    $links[] = route('directory_main_result', [$machine->_type->alias, $machine->region->alias, $machine->city->alias]);
                }
            }
        }

        $links = array_map(function ($element) use ($request) {

            return str_replace(env('APP_ROUTE_URL'), $request->input('for_site', 'trans-baza.ru'), $element);
        }, $links);

        return response()->json($links);

    }

    function getSitemapLnksKinosk(Request $request)
    {
        $links = [];
        foreach (Domain::where('alias', '!=', 'ru')->get() as $domain) {
           $links = array_merge($links, $this->getSitemapLinksforDomain($domain));
        }

        return response()->json($links);
    }

    function getSitemapLinksforDomain($domain)
    {
        app()->setLocale($domain->options['default_locale']);

        $cities = City::forDomain($domain->alias)->get();

        $categories = Type::whereHas('machines', function ($q) use ($domain) {
            $q->whereHas('region', function ($q) use ($domain) {
                $q->forDomain($domain->alias);
            });
        })->get();

        $articles = Article::where('is_publish', 1)->forDomain($domain->alias)->whereIn('type', ['article', 'news'])->get();

        $machines = Machinery::forDomain($domain->alias)->get();

        $links = [];

        $links[] = route('index_page');
        foreach ($articles as $article) {

            if ($article->type === 'news') {

                $route = route('get_news_article_kinosk', ['country' => $domain->alias, 'locale' => $domain->options['default_locale'], 'alias' => $article->alias]);
            } elseif ($article->type === 'article') {
                $route = route('get_article_kinosk', ['country' => $domain->alias, 'locale' => $domain->options['default_locale'], 'alias' => $article->alias]);
            } else {
                $route = route('article_index_kinosk', ['country' => $domain->alias, 'locale' => $domain->options['default_locale'], 'alias' => $article->alias]);
            }
            $links[] = $route;
        }

        foreach ($machines as $machine) {
            $links[] = $machine->rent_url;
        }

        foreach ($categories as $category) {
            $links[] = route('australia_directory', ['country' => $domain->alias, 'locale' => $domain->options['default_locale'], 'category_alias' => $category->alias]);
        }

        foreach ($cities as $city) {
            foreach ($city->machines->groupBy('type') as $type => $machines) {
                foreach ($machines as $machine) {
                    $links[] = route('australia_directory', [
                        'country' => $domain->alias,
                        'locale' => $domain->options['default_locale'],
                        'category_alias' => $machine->_type->alias,
                        'region' => $machine->region->alias,
                        'city' => $machine->city->alias]);
                }
            }
        }

        $links = array_map(function ($element) use ($domain) {

            return str_replace(env('APP_ROUTE_URL'), $domain->pure_url, $element);
        }, $links);

        return $links;

    }


    static function generateSitemapServices()
    {
        $i = 0;
        SeoServiceDirectory::chunk(50000, function ($services) use (&$i) {
            $filename = "sitemaps/sitemap_service{$i}.xml";
            $data = response()->view('sitemaps.sitemap_services', compact('services'))->getContent();
            Storage::disk()->put($filename, '');
            ++$i;
            dispatch(new PushCollectionToSitemapFile($data, $filename));
            unset($data);
        });

    }

    static function generateSitemapSeoCategory()
    {
        $a = 0;
        SeoContent::chunk(50000, function ($seo) use (&$a) {
            $filename = "sitemaps/sitemap_phonebook{$a}.xml";
            $data = response()->view('sitemaps.sitemap_directory', compact('seo'))->getContent();
            Storage::disk()->put($filename, '');
            ++$a;
            dispatch(new PushCollectionToSitemapFile($data, $filename));
            unset($data);
        });
    }

    function generateRSS()
    {
        $articles = Article::whereIsPublish(1)->get();

        return response()->view('rss', compact('articles'))->header('Content-Type', 'text/xml');
    }

    function sitemap()
    {
        set_time_limit(0);

        return self::siteMapContent()->header('Content-Type', 'application/xml');
    }

    function getJsonLocales(Request $request)
    {

        $request->validate([
            'locale' => 'required|in:ru,en,th,it'
        ]);


        $locale = $request->input('locale', 'ru');

//        foreach (config()->get('app.locales') as $lang){
//        }
        $strings = Cache::remember("lang_{$locale}", 60, function () use ($locale){

            $strings = [];
         /*   $files = glob(resource_path());
            logger(json_encode($files));
            foreach ($files as $file) {
                $messages = require $file;
                foreach ($messages as $key => &$val) {
                    $val = adjustString($val);
                }
                $name = basename($file, '.php');
                $strings[$locale][$name] = $messages;
            }*/
         $strings = [];
         $this->parseLocaleRecursive($strings,resource_path('lang/' . $locale . '/'), $locale);

          return $strings;
        });


        return $request->filled('responseJson')
            ? response()->json($strings[$locale])
            : response()->view('includes.locale', compact('strings'))->header('Content-Type', 'text/javascript');
    }

    private function parseLocaleRecursive(&$strings, $path, $locale, $prefix = '')
    {
        $files = glob($path . '*');
        foreach ($files as $file) {
            if(is_dir($file) && $file !== $path) {

                 $this->parseLocaleRecursive($strings, $file . '/', $locale, ($prefix ? $prefix . '/' : '') . basename($file));
            }

            if(getFileExtensionFromString($file) !== 'php') {
                continue;
            }
            $messages = require $file;
            if(!is_array($messages))
                continue;

            foreach ($messages as $key => &$val) {
                $val = adjustString($val);
            }
            $name = basename($file, '.php');
            $strings[$locale][(($prefix ? "{$prefix}/" : '') . $name)] = $messages;
        }

        return $strings;
    }
}
