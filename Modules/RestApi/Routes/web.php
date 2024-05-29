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

    Route::prefix('rest/v1')->group(function(){

        Route::prefix('helpers')->group(function (){

            Route::get('check-company/{company}', 'HelpersController@checkCompany');
            Route::post('request-demo', 'HelpersController@demoRequest');

            Route::get('sitemap', 'HelpersController@getSitemapLinks');

            Route::get('popular-offers', 'HelpersController@popularOffers');

            Route::get('geo-category', 'LocationController@getCategories');
            Route::get('brands', 'HelpersController@getBrands');

            Route::get('geo-top-category', 'LocationController@getTopCategories');

            Route::get('search-position', 'LocationController@searchPosition');

            Route::get('html-analytic', 'HelpersController@getAnalytic');

            Route::get('category/{id}/optional-attributes', 'HelpersController@getOptionalAttributes');

            Route::post('upload-documents', 'HelpersController@uploadDocuments')->middleware('auth:api');

            Route::post('upload-contracts', 'HelpersController@uploadContracts')->middleware('auth:api')->name('api_upload_documents');
        });

        Route::prefix('content')->group(function() {

            Route::get('faq', 'ArticlesController@getFaq')->middleware(['auth:api']);

            Route::get('news', 'ArticlesController@getNews');

            Route::get('knowledge-base', 'ArticlesController@knowledgeBase');

            Route::get('news/{alias}', 'ArticlesController@getNewsArticle');

            Route::get('notes/{alias}', 'ArticlesController@getArticle');

            Route::get('static/{alias}', 'ArticlesController@getContent');

            Route::get('{alias}', 'ArticlesController@getStaticContent');
        });

        Route::prefix('user/customer')->group(function() {

            Route::get('fast-order', 'HelpersController@fastOrder');

        });

        Route::prefix('vehicle')->group(function() {

            Route::get('alias/{alias}', 'SearchController@getByAlias')->middleware('append_seo');

            Route::post('{id}/calculate-cost', 'SearchController@calculateCost');

        });

        Route::get('get-config', 'HelpersController@getConfig');

        Route::get('contacts', 'HelpersController@getContacts');

        Route::get('categories/{id?}', 'HelpersController@getCategories')->name('api_get_categories');

        Route::get('catalog', 'HelpersController@getCatalog')->middleware('append_seo');

        Route::get('get-regions/{category_id?}', 'HelpersController@getRegions');

        Route::get('get-cities/{region_id?}', 'HelpersController@getCities');

        Route::get('search_vehicles', 'SearchController@searchMachines')->middleware('append_seo')->name('search_vehicles');

        Route::get('search-city', 'HelpersController@searchCity')->name('search_city');

        Route::post('prepare-order', 'SearchController@prepareOrderDetails')->name('prepare_order_details');

        Route::get('search-initial-data', 'HelpersController@initialData')->middleware('append_seo')->name('initial_data_for_search');

        Route::get('index-initial-data', 'HelpersController@initialIndexData')->name('initial_data_for_index');

        Route::get('profile-initial-data', 'HelpersController@initialProfileData')->middleware('auth:api')->name('initial_data_for_profile');

        Route::get('edit-vehicle-helpers', 'HelpersController@editVehicleHelpers')->middleware('auth:api')->name('vehicle_helpers');

        Route::post('upload-image', 'HelpersController@uploadImage')->middleware('auth:api')->name('api_upload_image');


        Route::prefix('auth')->group(function(){

            Route::post('hash', 'AuthController@authHash')->middleware(['throttle:6,1']);

            Route::post('login', 'AuthController@login')->middleware(['guest', 'throttle:6,1']);

            Route::get('get', 'AuthController@getUser')->middleware('auth:api');

            Route::post('logout', 'AuthController@logout');

            Route::post('upload-avatar', 'HelpersController@uploadAvatar')->middleware('auth:api');

            Route::post('register', 'AuthController@register')->middleware(['guest', 'throttle:10,1']);

            Route::get('social-vk', 'SocialController@vkAuth')->middleware(['guest', 'throttle:6,1']);

            Route::get('social-fb', 'SocialController@fbAuth')->middleware(['guest', 'throttle:6,1']);

            Route::get('social-linkedin', 'SocialController@LinkedAuth')->middleware(['guest', 'throttle:6,1']);

            Route::post('guest-phone-confirm', 'AuthController@preConfirmPhone')->middleware(['guest', 'throttle:10,1']);

            Route::post('check-code', 'AuthController@checkCode')->middleware(['guest', 'throttle:10,1']);

        });

        Route::prefix('subscribe')->group(function(){

            Route::post('add', 'SubscribesController@addSubscribe');

            Route::post('message', 'SubscribesController@sendInfoMessage');

        });


    });
};




Route::group(['domain' => /*'api.' .*/ env('APP_ROUTE_URL')], $routes);