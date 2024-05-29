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

use Illuminate\Http\Request;

Route::prefix('fmsapi')->group(function() {
    //Route::get('/', 'FMSAPIController@index');



//    Route::middleware('auth:api')->post('rest', 'FMSAPIController@rest');

});
