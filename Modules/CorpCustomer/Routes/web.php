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

$routes = function () {
    Route::prefix('rest/v1')->group(function () {

        Route::prefix('user/corporate')->middleware('auth:api')->group(function () {

            Route::prefix('helpers')->group(function() {

                Route::post('dadata-request', 'HelpersController@daDataHelper');
                Route::post('dadata-bik', 'HelpersController@searchByBik');

                Route::post('dadata', 'HelpersController@dadataAction');

            });

            Route::get('/', 'CorpCustomerController@index')->name('corp_index');

            Route::resource('brands', 'BrandsController');

            Route::resource('banks', 'BanksController');

            Route::resource('companies', 'CompanyController')->except(['create', 'edit']);

            Route::post('add-employee', 'CompanyController@addEmployee')->name('add_employee');

            Route::get('accept-employee/{link}', 'CompanyController@acceptEmployee')->name('accept_employee');


        });
    });
};

Route::group(['domain' => /*'api.' .*/ env('APP_ROUTE_URL')], $routes);
