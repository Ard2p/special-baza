<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use GuzzleHttp\Client;

$routes = function (){

//    Route::middleware('auth')->get('/', 'IntegrationsController@index');

    Route::middleware('auth')->post('/', 'IntegrationsController@update')->name('update_api');



    Route::prefix('v1')->group(function(){

        Route::prefix('beeline')->group(function(){
            Route::any('postback/{path?}', function (\Illuminate\Http\Request $request, $token) {



                $fileContents = str_replace(array("\n", "\r", "\t"), '', $request->getContent());
                $fileContents = trim(str_replace('"', "'", $fileContents));
                $fileContents = str_replace(':type', "type", $fileContents);
                $fileContents = str_replace([
                    '-xsi1:',
                    '-xsi1',
                    ':xsi',
                    'xsi:',
                    'xsi1',
                    '-xsi',
                ], "", $fileContents);
              //  logger($fileContents);
                $simpleXml = new SimpleXMLElement($fileContents);
                $json = json_encode($simpleXml, JSON_NUMERIC_CHECK);
              //  logger($json);
              //  logger(  "{$request->method()} {$request->path()}");
                $b = \Modules\Integrations\Entities\Beeline\BeelineTelephony::query()->where('url_token', $token)->first();

                $b?->parseCall(json_decode($json, true));


            })->where('path', '.*');
        });
        Route::prefix('bot')->group(function(){

            Route::get('orders/media/{id}', 'TelegramBotController@getMedia')->name('order_media');

           Route::get('driver', 'TelegramBotController@getDriver');
           Route::get('driver-order', 'TelegramBotController@getDriverOrder');

           Route::post('find-order/{id}', 'TelegramBotController@findOrder');

           Route::post('order/{id}/media', 'TelegramBotController@uploadOrderMedia');

           Route::post('driver/{id}/media', 'TelegramBotController@uploadMedia');

           Route::post('driver/action/{action}', 'TelegramBotController@driverAction');

           Route::post('auth', 'TelegramBotController@mechanicAuth');

           Route::post('machinery-event', 'TelegramBotController@machineryEvent');

           Route::get('search-machinery', 'TelegramBotController@searchMachinery');

           Route::post('attach-report', 'TelegramBotController@addReportToEvent');

           Route::post('validation', 'TelegramBotController@eventValidation');

        });

        Route::prefix('appraiser')->group(function(){

            Route::get('{branch_id}/machineries', 'AppraiserController@getMachineries');

        });

        Route::get('docs', 'IntegrationsController@docs')->name('api_docs');

        Route::get('docs/proxy', 'IntegrationsController@proxyDocs');


        Route::middleware('guest')->post('login', 'HelpersDataController@login');

        Route::middleware('auth:api')->get('cities', 'HelpersDataController@getAllCities');

        Route::middleware('auth:api')->get('regions/{id}/cities/{city_id?}', 'HelpersDataController@getCities');

        Route::middleware('auth:api')->get('regions/{region_id?}', 'HelpersDataController@getRegions');

        Route::middleware('auth:api')->get('categories/{category_id?}', 'HelpersDataController@getCategories');

        Route::middleware('auth:api')->get('brands/{brand_id?}', 'HelpersDataController@getBrands');


        Route::prefix('users')->group(function() {

            Route::middleware('auth:api')->get('all', 'UserController@allUsers');

            Route::middleware('auth:api')->get('get/{id}', 'UserController@getUser');

            Route::middleware('auth:api')->delete('delete/{id}', 'UserController@deleteUser');

            Route::middleware('auth:api')->post('restore/{id}', 'UserController@restoreUser');

            Route::middleware('auth:api')->patch('update/{id}', 'UserController@updateUser');

            Route::middleware('auth:api')->post('add', 'UserController@registerUser');

            Route::middleware('auth:api')->get('{user_id}/contractor-orders/{order_id?}', 'ProposalsController@getOrders');

            Route::middleware('auth:api')->delete('{user_id}/contractor-orders/{order_id}', 'ProposalsController@refuse');

            Route::middleware('auth:api')->get('{user_id}/customer-orders/{order_id?}', 'ProposalsController@getProposals');

            Route::middleware('auth:api')->delete('{user_id}/customer-orders/{order_id}', 'ProposalsController@refuseByCustomer');

        });
        Route::prefix('orders')->group(function() {

            Route::middleware('auth:api')->get('all', 'ProposalsController@getAllOrders');

            Route::middleware('auth:api')->post('{id}/send-vehicle-coordinates/{vehicle_id}', 'ProposalsController@addMachineryCoordinate');

            Route::middleware('auth:api')->delete('{id}/refuse', 'ProposalsController@refuse');

        });

        Route::prefix('proposals')->group(function() {

            Route::middleware('auth:api')->post('{user_id}/add', 'ProposalsController@createProposal');

        });

        Route::prefix('vehicles')->group(function() {

            Route::middleware('auth:api')->get('all/{user_id?}', 'MachineryController@allVehicles');

            Route::middleware('auth:api')->get('get/{id}', 'MachineryController@getVehicle');

            Route::middleware('auth:api')->delete('delete/{id}', 'MachineryController@deleteVehicle');

            Route::middleware('auth:api')->patch('update/{id}', 'MachineryController@updateVehicle');

            Route::middleware('auth:api')->post('add', 'MachineryController@addVehicle');

            Route::middleware('auth:api')->post('{id}/events/add', 'CalendarController@addEvent');

            Route::middleware('auth:api')->post('{id}/events/set-available', 'CalendarController@setFree');

            Route::middleware('auth:api')->delete('{id}/events/delete/{event_id}', 'CalendarController@deleteEvent');

            Route::middleware('auth:api')->patch('{id}/events/update/{event_id}', 'CalendarController@changeEvent');

            Route::middleware('auth:api')->get('{id}/events/{event_id?}', 'CalendarController@getEvents');

        });


    });

    Route::prefix('rest/v1')->group(function() {


        Route::prefix('1c')->middleware('auth:api')->group(function(){

            Route::get('{branch_id}/connect', 'OneCController@getConnector');
            Route::post('{branch_id}/connect', 'OneCController@connect');
            Route::post('{branch_id}/check-connection', 'OneCController@checkConnection');
            Route::get('searchByInn', 'OneCController@searchByInn');

        });

        Route::prefix('amo')->group(function() {

            Route::get('auth', 'AmoController@auth')->middleware('auth:api');

            Route::get('set-token', 'AmoController@setToken')->name('amo_redirect_url');

            Route::prefix('webhooks')->group(function() {

                Route::any('new-lead', 'AmoController@newLeadHook')->name('amo_lead_hook');

            });


        });
        Route::prefix('mail-connector')->group(function() {

            Route::delete('', 'MailConnectorsController@delete')->middleware('auth:api');
            Route::patch('', 'MailConnectorsController@update')->middleware('auth:api');
            Route::post('', 'MailConnectorsController@create')->middleware('auth:api');
            Route::get('', 'MailConnectorsController@getConnector')->middleware('auth:api');

        });

        Route::prefix('yandex')->group(function() {
           Route::resource('disk', 'YandexController')->only(['index', 'store']);
        });

        Route::prefix('telephony')->group(function() {

            Route::prefix('megafon')->group(function() {

                Route::get('', 'Telephony\MegafonController@getAccount')->middleware('auth:api');

                Route::post('create', 'Telephony\MegafonController@createAccount')->middleware('auth:api');

                Route::delete('remove', 'Telephony\MegafonController@removeAccount')->middleware('auth:api');

                Route::any('postback', 'Telephony\MegafonController@postback')->name('megafon_callback');
            });

            Route::prefix('uis')->middleware('auth:api')->group(function() {

                Route::get('', 'Telephony\UisController@getAccount');

                Route::post('create', 'Telephony\UisController@createAccount');

                Route::delete('remove', 'Telephony\UisController@removeAccount');

            });

            Route::prefix('sipuni')->middleware('auth:api')->group(function() {

                Route::get('', 'Telephony\SipuniController@getAccount');

                Route::post('create', 'Telephony\SipuniController@createAccount');

                Route::delete('remove', 'Telephony\SipuniController@removeAccount');

            });

            Route::prefix('mango')->middleware('auth:api')->group(function() {

                Route::get('', 'Telephony\MangoController@getAccount');

                Route::post('create', 'Telephony\MangoController@createAccount');

                Route::delete('remove', 'Telephony\MangoController@removeAccount');

            });

            Route::prefix('beeline')->middleware('auth:api')->group(function() {

                Route::get('', 'Telephony\BeelineController@getAccount');

                Route::post('create', 'Telephony\BeelineController@createAccount');

                Route::delete('remove', 'Telephony\BeelineController@removeAccount');

            });
        });



    });

};



Route::group([], $routes);
