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


$widget = function () {
    Route::post('/rent-proposal/{key}', '\App\Http\Controllers\ProposalsController@rentProposal')->name('make_rent_from_widget');

    Route::get('/widget/{key}', '\App\Http\Controllers\Widget\WidgetController@generateWidget')->name('widget_path');

    Route::get('/get-cities/{number}', '\App\Http\Controllers\HomeController@getCities');

    Route::get('/get-region/{number}', '\App\Http\Controllers\HomeController@getRegion');

    Route::group(['middleware' => ['web', 'guest']], function () {


        Route::get('/register', function () {
            return view('widget.register');
        })->name('register_widget_front');

        Route::post('/register', '\App\Http\Controllers\Widget\WidgetController@registerWidgetUser')->name('register_widget');


    });

    Route::group(['middleware' => ['widget']], function () {

        Route::get('/', '\App\Http\Controllers\Widget\WidgetController@index')->name('home_widget');

        Route::post('/', '\App\Http\Controllers\Api\WidgetController@home')->name('save_settings');

        Route::resource('widgets', '\App\Http\Controllers\Widget\WidgetController')->except('index');

        Route::post('/withdrawal', '\App\Http\Controllers\Widget\WidgetController@withdrawalRequest')->name('withdrawal');

        Route::get('/balance', '\App\Http\Controllers\User\FinanceController@index')->name('widget_finance');

        Route::get('/get-key/{id}', '\App\Http\Controllers\Api\WidgetController@getWidgetKey')->name('widget_key');

        Route::get('/balance_history_table', '\App\Http\Controllers\User\FinanceController@balanceHistoryTable');

        Route::get('/transactions_history_table', '\App\Http\Controllers\User\FinanceController@transactionsHistoryTable');


    });


};

Route::group(['domain' => 'webwidget.' . env('APP_ROUTE_URL')], $widget);