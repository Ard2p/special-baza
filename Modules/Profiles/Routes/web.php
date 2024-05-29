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

use App\User;

$prof_routes = function ($slug){
    Route::get('/robots.txt', function ($slug) {

        $work = User::whereContractorAlias($slug)->first();

        return response()->view('sitemaps.robots', compact('work'))->withHeaders(['Content-Type' => 'text/plain'])->setStatusCode(200);
    });
    Route::get('/', 'ProfilesController@getUserProfile')->name('user_public_page');
    Route::get('/spectehnika', 'ProfilesController@contractorPublicPage')->name('contractor_public_page');


};

Route::pattern('slug', '^(?!.*webwidget|.*office|.*stat|.*orders|.*api|.*partner).*$');

Route::group(['domain' => '{slug}.' . env('APP_ROUTE_URL')], $prof_routes);//->where('slug', '^(?!.*webwidget|.*office|.*stat|.*orders|.*partner).*$');


$routes = function (){

    Route::prefix('rest/v1')->group(function(){


        Route::prefix('user')->group(function(){

            Route::post('confirm-email', 'ProfilesController@confirmEmail');

            Route::post('reset-password', 'ProfilesController@resetPassowrd');

            Route::post('reset-password/check-token', 'ProfilesController@checkToken');

            Route::post('reset-password/new', 'ProfilesController@changeResetPassword');

            Route::post('documents/{id}/up', 'ProfilesController@upDocument')->middleware(['auth:api']);
            Route::post('documents/{id}/down', 'ProfilesController@downDocument')->middleware(['auth:api']);
            Route::delete('documents/{id}', 'ProfilesController@deleteDocument')->middleware(['auth:api']);

            Route::get('notifications', 'NotificationsController@index')->middleware(['auth:api']);

            Route::delete('notifications/{id}', 'NotificationsController@destroy')->middleware(['auth:api']);

            Route::patch('notifications/settings', 'NotificationsController@settings')->middleware(['auth:api']);

        });


        Route::prefix('user/profile')->middleware('auth:api')->group(function(){

            Route::post('update', 'ProfilesController@updateProfile');

            Route::post('resend-token', 'ProfilesController@resendToken');

            Route::post('resend-sms', 'ProfilesController@resendSmsToken');

            Route::post('accept-sms', 'ProfilesController@smsConfirm');

            /*Route::prefix('requisites')->group(function() {

                Route::resource('individual', 'Requisites\IndividualController');

                Route::resource('legal', 'Requisites\LegalController');

            });*/

        });



    });
};




Route::group(['domain' => /*'api.' .*/ env('APP_ROUTE_URL')], $routes);
