<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('git-push-event', function (Request $request) {

    $output = [];
    /*exec('cd /home/transbaza/web/trans-baza.com/public_html && git fetch --all && git reset --hard origin/master && git pull origin master', $output);

    \Illuminate\Support\Facades\Log::info(implode(' ', $output));*/
    $path = $request->has('deploy') ? '/home/transbaza/web/trans-baza.ru/public_html/deploy.sh' : '/home/transbaza/web/trans-baza.com/public_html/deploy.sh';
    $process = new Symfony\Component\Process\Process("sh {$path}");
    $process->setTimeout(3600 * 10);
    $process->setIdleTimeout(3600 * 10);
    $process->run();

    \Illuminate\Support\Facades\Log::info($process->getOutput());
    return response('OK');
});

Route::post('git-push-appraiser', function (Request $request) {

    $output = [];
    /*exec('cd /home/transbaza/web/trans-baza.com/public_html && git fetch --all && git reset --hard origin/master && git pull origin master', $output);

    \Illuminate\Support\Facades\Log::info(implode(' ', $output));*/
    $path = '/home/transbaza/web/appraiser.trans-baza.com/public_html/deploy.sh';
    $process = new Symfony\Component\Process\Process("sh {$path}");
    $process->setTimeout(3600 * 10);
    $process->setIdleTimeout(3600 * 10);
    $process->run();

    \Illuminate\Support\Facades\Log::info($process->getOutput());
    return response('OK');
});

Route::post('git-push-backend', function (Request $request) {

    $output = [];
    /*exec('cd /home/transbaza/web/trans-baza.com/public_html && git fetch --all && git reset --hard origin/master && git pull origin master', $output);

    \Illuminate\Support\Facades\Log::info(implode(' ', $output));*/
    $path = $request->has('deploy') ? '/home/transbaza/web/api.trans-baza.ru/public_html/deploy.sh' : '/home/transbaza/web/api-test.trans-baza.ru/public_html/deploy.sh';
    $process = new Symfony\Component\Process\Process("sh {$path}");
    $process->setTimeout(3600 * 10);
    $process->setIdleTimeout(3600 * 10);
    $process->run();

    \Illuminate\Support\Facades\Log::info($process->getOutput());
    return response('OK');
});

Route::post('sms-status', function (Request $request) {

   // \Illuminate\Support\Facades\Log::info(json_encode($request->all()));

});

Route::get('get-cities/{number}', 'HomeController@getFilterCities');

Route::post('dep-drop-city', 'HomeController@getDepDropCity')->name('dep_drop');

Route::get('get-regions/{number}', 'HomeController@getFilterRegion');

Route::get('get-widget-cities/{number}', 'HomeController@getWidgetCities');

Route::get('get-country-regions/{number}', 'HomeController@getCountryRegions')->name('country_regions');

Route::get('get-widget-regions/{number}', 'HomeController@getWidgetRegions')->name('widget_types_url');

Route::get('get-widget-regions-form-machine/{number}', 'HomeController@getWidgetRegionsForMachine')->name('widget_machines_url');

Route::get('get-widget-types/{number}', 'HomeController@getWidgetTypesRegion');

Route::get('get-widget-machines/{number}', 'HomeController@getWidgetMachinesRegion');

Route::get('get-user-cities/{number}', 'HomeController@getUserCities');

Route::get('get-user-service-cities/{number}', 'HomeController@getUserServiceCities');

Route::get('get-tb-feel-cities/{type}/{number}', 'HomeController@getTBFeelCities');

Route::get('get-user-regions/{number}', 'HomeController@getUserRegions')->name('widget_service_types_url');

Route::get('get-user-service-regions/{number}', 'HomeController@getUserServiceRegions')->name('widget_user_types_url');

Route::get('get-user-types/{number}', 'HomeController@getUserTypesRegion');

Route::get('get-user-service-types/{number}', 'HomeController@getUserTypesRegion');

Route::get('regions', 'Api\WidgetController@getRegions');

Route::post('make-proposal', 'Api\WidgetController@makeProposal')->name('make_proposal');

Route::get('my-widget/{key}', 'Api\WidgetController@getWidgetScript')->where('any', '.*')->name('widget_script');

Route::get('stats', 'Stat\StatController@getStats')->name('get_stats');

Route::get('stats-region', 'Stat\StatController@getRegionStats')->name('get_region_stats');

Route::get('stats-info', 'Stat\StatController@moreInfo')->name('more_info');

Route::get('stats-user-info', 'Stat\StatController@moreUserInfo')->name('more_user_info');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
