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

$routes = function (){

    Route::prefix('rest/v1/marketplace')->group(function(){
        Route::post('request', 'MarketplaceController@makeRequest');

        Route::get('machineries/{id}/available-dates', 'MarketplaceController@availableDates');

        Route::get('machineries/{id}/events', 'MarketplaceController@getEvents');

        Route::get('machineries/{id}/actual-delivery-cost', 'MarketplaceController@getActualDeliveryCost');
    });
};




Route::group(['domain' => /*'api.' .*/ env('APP_ROUTE_URL')], $routes);