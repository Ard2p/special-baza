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

/*$search_routes = function (){
    Route::prefix('search')->group(function() {
        Route::get('/', 'SearchController@index');
    });

    Route::prefix('spectehnika')->group(function() {

        Route::get('/', 'SearchController@directoryMain')->name('directory_main');


        Route::get('arenda-{category}', 'SearchController@directoryMainCategory')->name('directory_main_category');

        Route::get('arenda-{category}/{region}', 'SearchController@directoryMainCategory')->name('directory_main_region');

        Route::get('arenda-{category}/{region}/{city}', 'SearchController@directoryMainCategory')->name('directory_main_result');

        Route::get('arenda-{category}/{region}/{city}/{alias}', 'SearchController@showRent')->name('show_rent');
    });
};

Route::prefix('{lang?}')->where(['lang' => '(' . implode(config('app.locales'), '|') . ')'])
    ->group($search_routes);

Route::prefix('')->group($search_routes);*/




