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


Route::prefix('telephony')->group(function () {

    Route::post('/incoming_call', 'TelephonyController@pushCall');


    Route::group(['middleware' => ['admin', 'no_edit_mode', 'block', 'freeze']], function () {

        Route::get('/get-calls', 'TelephonyController@getCalls')->name('get_telephony_calls');

        Route::get('/admin', 'TelephonyController@index');

        Route::post('/new-proposal', 'TelephonyController@newProposal')->name('phone_proposal');


    });

});
