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

use Modules\PartsWarehouse\Http\Controllers\WarehousePartSetController;

$routes = function () {

    Route::prefix('rest/v1/parts-warehouse')->middleware(['auth:api'])->group(function () {
        Route::prefix('helpers')->group(function (){

            Route::get('parts-check', '\Modules\AdminOffice\Http\Controllers\Warehouse\PartsController@partsCheck');
            Route::get('parts', '\Modules\AdminOffice\Http\Controllers\Warehouse\PartsController@index');

            Route::get('search-part', 'PartsWarehouseController@searchPart');

            Route::get('add-part', 'PartsWarehouseController@addPartHelper');

            Route::get('groups', 'PartsWarehouseController@getGroups');

            Route::get('parts', 'PartsWarehouseController@getParts');

        });

        Route::prefix('directory')->group(function (){

            Route::get('parts', 'PartsWarehouseController@index');

            Route::get('available-parts', 'PartsWarehouseController@getAvailableParts');

            Route::post('parts', 'PartsWarehouseController@store');

            Route::patch('parts/{id}', 'PartsWarehouseController@update');

            Route::post('attach', 'PartsWarehouseController@addToDirectory');

            Route::delete('detach', 'PartsWarehouseController@removeFromDirectory');
        });

        Route::prefix('shop')->group(function (){

            Route::post('order', 'Shop\RequestsController@fastOrder');

            Route::post('requests/{id}/sale', 'Shop\RequestsController@sale');

            Route::resource('requests', 'Shop\RequestsController')->except(['edit', 'create']);

            Route::post('sales/{id}/invoice', 'Shop\SalesController@invoice');

            Route::get('sales/{id}/documents', 'Shop\SalesController@getDocuments');

            Route::resource('sales', 'Shop\SalesController')->except(['edit', 'create']);


        });

        Route::patch('warehouse-sets/{id}', [WarehousePartSetController::class,'update']);

        Route::resource('providers', 'ProvidersController')->except(['edit', 'create']);

        Route::resource('posting', 'PostingController')->except(['edit', 'create']);

        Route::get('/groups',  '\Modules\AdminOffice\Http\Controllers\Warehouse\PartsGroupsController@index');

        Route::resource('stocks',  'StocksController')->except(['create', 'edit']);

        Route::get('items/{id}/available',  'StockItemsController@getAvailableStocks');

        Route::get('items/{id}/consumption',  'StockItemsController@getPartsConsumption');

        Route::patch('items/{id}/update',  'StockItemsController@updateField');

        Route::resource('items',  'StockItemsController')->except(['create', 'edit']);

        Route::get('available-for-sale',  'StockItemsController@getAvailableItems');
        Route::get('available-for-rent',  'StockItemsController@getAvailableRentItems');




    });

};

Route::group(['domain' => /*'api.' .*/ env('APP_ROUTE_URL')], $routes);
