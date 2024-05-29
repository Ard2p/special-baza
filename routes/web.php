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

use App\Content\Faq;
use App\Http\Controllers\Avito\AvitoController;
use App\Http\Controllers\ClientBankController;
use App\Http\Controllers\TestController;
use App\Machinery;
use App\Machines\Type;
use App\Support\Region;
use App\User;
use Modules\CompanyOffice\Http\Controllers\Googleapi\GoogleApiSettingController;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\PartsWarehouse\Entities\Posting;
use Rap2hpoutre\FastExcel\FastExcel;

/*Route::get('/500', function () {
  return  '₽';



});*/
Route::post('hook/{branch}', [ClientBankController::class, 'hook']);
//Route::get('test', [TestController::class, 'index']);
Route::get('oauth2callback', [GoogleApiSettingController::class, 'store']);
Route::post('alfa-callback', [AvitoController::class, 'alfaCallback']);
Route::get('alfa-callback', [AvitoController::class, 'alfaCallback']);

$redirect = function () {
    Route::any('/{any}', function () {
        $full = request()->path();
        return redirect()->away('https://trans-baza.ru/'.trim($full, '/'))->setStatusCode(302);
    })->where('any', '.*');
};

Route::middleware([
    'auth.avito'
])->prefix('integration/v1')->group(function () {
    Route::get('get-order', [AvitoController::class, 'getOrder']);
    Route::post('create-order', [AvitoController::class, 'createOrder']);
    Route::post('cancel-order', [AvitoController::class, 'cancelOrder']);
    Route::post('support-request', [AvitoController::class, 'supportRequest']);
});
Route::group(['domain' => 'xn--80aaac5a7atkf.xn--p1ai'], $redirect);
Route::group(['domain' => 'www.'.env('APP_ROUTE_URL')], $redirect);
Route::group(['domain' => '*.trans-baza.com'], $redirect);
Route::group(['domain' => 'trans-baza.com'], $redirect);


Route::get('/parse-24', function () {

});
$routes = function () {

    Route::get('/js/lang.js', 'HomeController@getJsonLocales')->name('assets.lang');

    Route::post('/change-lng/{lng?}', 'User\OfficeController@changeLanguage')->name('change_language');

    Route::post('/change-lng-mode', 'User\OfficeController@changeEditMode')->name('change_edit_mode');

    Route::post('/chat/hello/{id}', 'ChatController@hello');

    Route::get('/image-check/{id}.png', 'Marketing\ShareListController@getPixel')->name('check_email_pixel');

    Route::get('/live_counter', 'Marketing\ShareListController@getLiveCounter')->name('get_live_counter');

    Route::get('/fsk', 'Marketing\ShareListController@getFsk')->name('get_fsk');

    Route::get('/image-friend-check/{id}.png',
        'Marketing\ShareListController@getFriendPixel')->name('check_email_friend_pixel');


    Route::get('/image-subscribe/{id}.png', 'Marketing\SubscribeController@getPixel')->name('check_subscribe_pixel');

    Route::get('/image-mailing/{id}.png', 'Marketing\SubscribeController@getMailingPixel')->name('check_mailing_pixel');


    Route::get('/', function () {
        $full = request()->path();
        return redirect()->away('https://trans-baza.ru/'.trim($full, '/'))->setStatusCode(302);
    })->name('index_page');


    Route::get('/robots-origin.txt', function () {

        $file = Storage::disk('files')->get(\App\Option::find('robots')->value);

        return response($file, 200, ['Content-Type' => 'text/plain'])->setStatusCode(200);
    });

    Route::get('/sitemap_test.xml', 'HomeController@sitemap')->name('sitemap_');

    Route::get('/sitemap-links', 'HomeController@getSitemapLnks');
    Route::get('/sitemap-links-kinosk', 'HomeController@getSitemapLnksKinosk');


    Route::get('/sitemap-origin.xml', function () {

        $file = Storage::disk('files')->get(\App\Option::find('sitemap')->value);

        return response($file, 200, ['Content-Type' => 'application/xml']);

    });

    Route::get('/spectehnika', 'Stat\StatController@directoryMain')->name('directory_main');

    Route::get('/spectehnika/arenda-{category}',
        'Stat\StatController@directoryMainCategory')->name('directory_main_category');

    Route::get('/spectehnika/arenda-{category}/{region}',
        'Stat\StatController@directoryMainRegion')->name('directory_main_region');

    Route::get('/spectehnika/arenda-{category}/{region}/{city}',
        'Stat\StatController@directoryMainResult')->name('directory_main_result');

    Route::get('/spectehnika/arenda-{category}/{region}/{city}/{alias}',
        'Machinery\MachineryController@showRent')->name('show_rent');


    Route::get('/{country}-{locale}/heavy-equipment/rent-{category_alias}/{region?}/{city?}/{alias?}',
        'Stat\StatController@internationalCheck')->name('australia_directory');


    Route::get('/r/{hash}', function ($hash) {
        $link = \App\Service\RedirectLink::whereHash($hash)->firstOrFail();
        return redirect()->away($link);
    });

    Route::group(['middleware' => 'auth'], function () {

        Route::get('/testt', function () {

            $cities = \App\Machinery::with('city', '_type')
                ->get()
                ->groupBy(['city.id', '_type.id']);
            $cats = \App\Machines\Type::whereHas('machines')->get();
            return view('test', compact('cities', 'cats'));
        });


        Route::get('/new/user', 'User\OfficeController@profile')->name('profile_index')->middleware('auth');

        Route::resource('support', 'Support\TicketsController');


    });

    Route::group(['middleware' => ['guest']], function () {
        Route::prefix('rest/v1/auth')->group(function () {

            Route::get('/fb-redirect', 'Auth\FacebookLoginController@redirect')->name('facebook_redirect');
            Route::get('/fb-callback', 'Auth\FacebookLoginController@callback')->name('facebook_callback');

            Route::get('/vk-redirect', 'Auth\VkontakteLoginController@redirect')->name('vkontakte_redirect');
            Route::get('/vk-callback', 'Auth\VkontakteLoginController@callback')->name('vkontakte_callback');

            Route::get('/linkedin-redirect', 'Auth\LinkedinController@redirect')->name('linkedin_redirect');
            Route::get('/linkedin-callback', 'Auth\LinkedinController@callback')->name('linkedin_callback');

        });

    });

    Route::group(['middleware' => ['auth', 'block']], function () {

        Route::post('/user/close-ticker', 'User\OfficeController@closeTicker')->name('close_ticker');

        Route::get('/user/freeze', 'User\OfficeController@freeze');

        Route::get('/user/check-notifications', 'User\OfficeController@getAdminNotification');

        Route::get('/resend-token', 'User\OfficeController@resendToken');

        Route::post('/resend-sms', 'User\OfficeController@resendSmsToken')->name('resend_sms');

        Route::post('/accept-sms', 'User\OfficeController@smsConfirm')->name('accept_sms');

        Route::post('/load-avatar', 'User\OfficeController@loadAvatar')->name('load_avatar');

        Route::get('/delete-avatar', 'User\OfficeController@delAvatar')->name('delete_avatar');

        Route::get('/change-role', 'User\OfficeController@changeRole')->name('change_role');

        Route::post('/user', 'User\OfficeController@changeProfile')->name('change_profile');

        Route::resource('individual_requisites', 'User\IndividualRequisitesController');

        Route::resource('entity_requisites', 'User\EntityRequisitesController');

    });


    Route::group(['middleware' => ['contentAdmin', 'no_edit_mode', 'block', 'freeze'], 'prefix' => 'translations'],
        function () {
//            \Vsch\TranslationManager\Translator::routes();
        });

    Route::post('/auth', 'Auth\LoginController@authUser');

    Route::get('/confirm/{token}', 'User\OfficeController@acceptToken');

    Auth::routes();

    Route::get('/articles', 'ArticleController@getArticles');

    Route::get('/turbo-rss', 'HomeController@generateRSS');

    Route::get('/{country}-{locale}/articles/{article}', 'ArticleController@getArticle')->name('get_article_kinosk');

    Route::get('/{country}-{locale}/news/{alias}', 'ArticleController@getNewsArticle')->name('get_news_article_kinosk');

    Route::get('/articles/{article}', 'ArticleController@getArticle')->name('get_article');

    Route::get('/news/{article}', 'ArticleController@getNewsArticle')->name('get_news_article');

    /*        Route::get('/{static}', 'ArticleController@static')
                ->where(['static' => 'about|for-customer|for-contractor|contacts|for-partner|rules']);*/

    Route::get('/{country}-{locale}/content/{article}', 'ArticleController@index')
        ->where('article',
            '^(?!.*login|.*v1|.*register|.*profile|.*telephony|.*rest|.*admin).*$')->name('article_index_kinosk');


    Route::get('/content/{article}', 'ArticleController@index')
        ->where('article',
            '^(?!.*login|.*v1|.*register|.*profile|.*telephony|.*rest|.*admin).*$')->name('article_index');

};


/*Route::group(['domain' => 'orders.' . env('APP_ROUTE_URL')], $routes);*/

/*
Route::group(['domain' => 'partner.' . env('APP_ROUTE_URL')], $partner);*/


Route::group([], $routes);
