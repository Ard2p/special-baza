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

use App\Http\Controllers\Avito\AdminAvitoController;
use App\Http\Controllers\Avito\AvitoController;
use Modules\AdminOffice\Http\Controllers\InsuranceController;
use Modules\ContractorOffice\Http\Controllers\Scoring\ScoringController;

$routes = function () {

    Route::prefix('rest/v1/admin')->group(function () {
        Route::middleware(['auth:api'])->group(function () {
            Route::get('/scorings', [ScoringController::class, 'index'])->name('scoring.index');

        });

        Route::prefix('insurance')->middleware(['auth:api', 'contentAdmin'])->group(function () {
            Route::get('/certificates', [InsuranceController::class, 'get'])->name('insurance.get');
            Route::get('/export-certificates', [InsuranceController::class, 'export'])->name('insurance.export');
        });

        Route::prefix('helpers')->middleware(['auth:api', 'admin_helper_access'])->group(function () {

            Route::get('domains', 'AdminOfficeController@getDomains');

            Route::post('ck-upload', 'AdminOfficeController@ckUpload');

            Route::get('content-data', 'AdminOfficeController@getContentData');

            Route::get('index-data', 'AdminOfficeController@indexData');

            Route::post('upload-image', 'AdminOfficeController@uploadImage');

            Route::get('countries', 'AdminOfficeController@getCountries');

            Route::get('generate-link-hash', 'AdminOfficeController@generateLinkHash');

            Route::get('regions', 'AdminOfficeController@getRegions');

            Route::get('edit-user-data', 'AdminOfficeController@getEditUserFormData');

            Route::get('search-users', 'UsersController@searchUser');

            Route::get('search-user-data', 'AdminOfficeController@getSearchUserInitialData');

            Route::get('search-order-data', 'AdminOfficeController@getSearchOrderInitialData');

            Route::get('search-payments-data', 'AdminOfficeController@getSearchPaymentsInitialData');

            Route::get('search-vehicles-data', 'AdminOfficeController@getSearchVehiclesInitialData');

            Route::get('edit-vehicles-data', 'AdminOfficeController@getEditVehiclesInitialData');

            Route::get('access-blocks', 'AdminOfficeController@getAccessBlocks');

            Route::get('phone-info', 'AdminOfficeController@getPhoneInfo');

            Route::get('lead/create', 'Dispatcher\LeadsController@createHelper');

            Route::get('tariffs', 'AdminOfficeController@getCategoriesTariffs');

            Route::get('vehicles-models', '\Modules\ContractorOffice\Http\Controllers\VehiclesController@getModels');

        });

        Route::prefix('companies/branches')->middleware(['auth:api', 'operator'])->group(function () {

            Route::get('roles', 'Companies\CompanyBranchesController@getBranchRoles');

            Route::get('{id}/vehicles', 'VehiclesController@userVehicles');

            Route::get('{id}/notes', 'Users\NotesController@index');

            Route::post('{id}/notes', 'Users\NotesController@store');

            Route::delete('{id}/notes/{note_id}', 'Users\NotesController@destroy');

            Route::delete('{id}/total-delete',
                'Companies\CompanyBranchesController@totalDelete')->middleware(['admin']);

            Route::post('{id}/invite-user', 'Companies\CompanyBranchesController@inviteUser')->middleware(['admin']);

            Route::post('{id}/remove-user', 'Companies\CompanyBranchesController@deleteUser')->middleware(['admin']);

        });
        Route::resource('companies/branches', 'Companies\CompanyBranchesController')->middleware(['auth:api', 'admin']);

        Route::resource('companies', 'Companies\CompaniesController')->middleware(['auth:api', 'admin']);

        Route::prefix('companies')->middleware(['auth:api', 'operator'])->group(function () {


        });

        Route::prefix('users')->group(function () {

            Route::get('excel', 'UsersController@excel');

            Route::post('ya-call', 'UsersController@storeYaCall')->middleware(['auth:api', 'admin']);

            Route::post('create', 'UsersController@createUser')->middleware(['auth:api']);

            Route::get('{id?}', 'UsersController@getUsers')->middleware(['auth:api', 'operator']);

            Route::post('{id}/update', 'UsersController@updateUser')->middleware(['auth:api', 'operator']);


            Route::delete('{id}/total-delete', 'UsersController@totalDelete')->middleware(['auth:api', 'admin']);

            Route::post('{id}/change-password', 'UsersController@changePassword')->middleware(['auth:api', 'admin']);

            Route::get('{id}/contractor/transactions',
                'UsersController@getContractorTransactions')->middleware(['auth:api', 'admin']);

            Route::get('{id}/contractor/balance', 'UsersController@getContractorBalance')->middleware([
                'auth:api', 'admin'
            ]);

            Route::get('{id}/action-audit', 'UsersController@getActionAudit')->middleware(['auth:api', 'admin']);

            Route::post('{id}/connect-module', 'UsersController@connectModule')->middleware(['auth:api', 'operator']);

            Route::post('{id}/predicted-categories',
                'UsersController@attachPredictedCategories')->middleware(['auth:api', 'operator']);
            Route::delete('{id}/predicted-categories',
                'UsersController@detachPredictedCategory')->middleware(['auth:api', 'operator']);
            Route::get('{id}/predicted-categories', 'UsersController@getPredictedCategories')->middleware([
                'auth:api', 'operator'
            ]);

            Route::prefix('{user_id}/corporate')->name('brands')->group(function () {

                Route::resource('brands', 'Users\BrandsController')->middleware(['auth:api', 'admin']);
            });

        });
        Route::prefix('marketing')->middleware(['auth:api', 'contentAdmin'])->group(function () {

            Route::get('subscribers', 'Marketing\SubscribersController@get');

            Route::prefix('templates')->group(function () {

                Route::resource('email', 'Marketing\EmailTemplatesController')->except(['create', 'edit']);

            });
        });

        Route::prefix('warehouse')->middleware(['auth:api', 'contentAdmin'])->group(function () {

            Route::resource('groups', 'Warehouse\PartsGroupsController')->except(['create', 'edit']);

            Route::patch('parts/{id}/detach-analogue', 'Warehouse\PartsController@detachAnalogue');

            Route::resource('parts', 'Warehouse\PartsController')->except(['create', 'edit']);


        });

        Route::prefix('faq')->middleware(['auth:api', 'contentAdmin'])->group(function () {

            Route::resource('content', 'Faq\ContentController')->except(['create', 'edit']);

            Route::resource('categories', 'Faq\CategoriesController')->except(['create', 'edit']);

            Route::resource('roles', 'Faq\RolesController')->except(['create', 'edit']);

        });

        Route::prefix('settings')->middleware(['auth:api'])->group(function () {

            Route::get('commission', 'SettingsController@getCommission');
            Route::post('commission', 'SettingsController@setCommission');

            Route::get('general', 'SettingsController@getGeneral');
            Route::post('general', 'SettingsController@setGeneral');

            Route::get('logger', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');

            Route::post('logger/crypt', 'SettingsController@getCryptName');
        });

        Route::prefix('feedback')->middleware(['auth:api', 'contentAdmin'])->group(function () {

            Route::post('create', 'FeedbackController@createFeedBack');

            Route::post('sort', 'FeedbackController@sortIt');

            Route::get('{id?}', 'FeedbackController@getFeedbacks');


            Route::post('{id}/update', 'FeedbackController@updateFeedBack');

            Route::delete('{id}/delete', 'FeedbackController@deleteFeedBack');


        });

        Route::prefix('contacts')->middleware(['auth:api', 'contentAdmin'])->group(function () {

            Route::get('company', 'ContactsController@getCompanyRequisites');

            Route::post('company', 'ContactsController@setCompanyRequisites');

            Route::post('create', 'ContactsController@create');

            Route::get('{id?}', 'ContactsController@get');

            Route::post('{id}/update', 'ContactsController@update');

            Route::delete('{id}/delete', 'ContactsController@delete');

        });

        Route::get('avito-orders/{id}', 'OrdersController@avitoDump')->middleware(['auth:api', 'admin']);
        Route::delete('avito-orders/{avito_order}', [AvitoController::class, 'deleteOrder']);
        Route::get('avito-orders', 'OrdersController@avitoOrders')->middleware(['auth:api', 'admin']);
        Route::get('avito-logs', 'OrdersController@avitoLogs')->middleware(['auth:api', 'admin']);

        Route::prefix('avito')->group(function () {
//        Route::prefix('avito')->middleware(['auth:api','admin'])->group(function () {
            Route::get('orders',  [AdminAvitoController::class, 'getOrders']);
            Route::get('orders-reference',  [AdminAvitoController::class, 'getAvitoOrdersReference']);
            Route::get('orders-results',  [AdminAvitoController::class, 'getAvitoOrdersResults']);
        });

        Route::prefix('orders')->group(function () {

            Route::get('{id?}', 'OrdersController@getOrders')->middleware(['auth:api', 'rp_user']);

            Route::post('{id}/update', 'OrdersController@updateOrder')->middleware(['auth:api', 'admin']);

            Route::delete('{id}/delete', 'OrdersController@cancelOrder')->middleware(['auth:api', 'admin']);

            Route::get('{id}/audit', 'OrdersController@getAudit')->middleware(['auth:api', 'admin']);


        });

        Route::prefix('content')->middleware(['auth:api', 'contentAdmin'])->group(function () {
            Route::delete('galleries/{id}', 'ArticlesController@deleteGallery');
        });
        Route::prefix('articles')->middleware(['auth:api', 'contentAdmin'])->group(function () {

            Route::resource('tags', 'Support\TagsController');

            Route::post('locale/create', 'ArticlesController@createArticleLocale');

            Route::post('locale/{id}/update', 'ArticlesController@updateLocale');

            Route::post('create', 'ArticlesController@createArticle');


            Route::get('{id?}', 'ArticlesController@getArticles');

            Route::post('{id}/update', 'ArticlesController@updateArticle');

            Route::delete('{id}/delete', 'ArticlesController@deleteArticle');

        });
        Route::prefix('seo-block')->middleware(['auth:api', 'contentAdmin'])->group(function () {

            Route::post('create', 'SeoBlockController@create');

            Route::get('{id?}', 'SeoBlockController@get');

            Route::post('{id}/update', 'SeoBlockController@update');

            Route::delete('{id}/delete', 'SeoBlockController@delete');

        });

        Route::post('payments/generate', 'PaymentsController@generate')->middleware([
            'auth:api', 'operator', 'admin_helper_access'
        ]);

        Route::prefix('payments')->middleware(['auth:api', 'admin'])->group(function () {

            Route::get('{id?}', 'PaymentsController@getPayments');

            Route::post('{id}/update', 'PaymentsController@updatePayment');

            Route::post('{id}/accept-invoice', 'PaymentsController@acceptInvoice');

            Route::get('invoice/{id}', 'PaymentsController@getInvoice');

            Route::post('invoice/{id}/pays', 'PaymentsController@addPay');


        });

        Route::prefix('call-center')->group(function () {


            Route::get('get-initial', 'CallCenterController@getInitial')->middleware(['auth:api', 'operator']);

            Route::get('sms-list', 'CallCenterController@smsList')->middleware(['auth:api', 'operator']);

            Route::get('call-record/{id}', 'CallCenterController@getCallStream'); //->middleware(['auth:api', 'admin']);

            Route::post('send-sms', 'CallCenterController@sendSms')->middleware(['auth:api', 'operator']);

            Route::get('calls', 'CallCenterController@getCalls')->middleware(['auth:api', 'operator']);
        });


        Route::prefix('vehicles')->name('vehicles')->middleware(['auth:api', 'operator'])->group(function () {

            Route::get('optional-attributes', 'VehiclesController@getOptionalAttributes');
            Route::get('models', '\Modules\ContractorOffice\Http\Controllers\VehiclesController@getModels')->name('models');

            Route::get('tariffs', '\Modules\ContractorOffice\Http\Controllers\VehiclesController@getTariffs')->name('tariffs');


            Route::get('{id?}', 'VehiclesController@getVehicles')->name('getVehicles');

            Route::post('add', 'VehiclesController@store')->name('store');

            Route::post('{id}/update', 'VehiclesController@update')->name('1');

            Route::get('{id}/work-hours', 'Vehicles\WorkHoursController@getWorkHours')->middleware(['operator'])->name('2');

            Route::put('{id}/work-hours', 'Vehicles\WorkHoursController@updateWorkHours')->middleware(['operator'])->name('3');

            Route::get('{id}/audit', 'VehiclesController@getAudit')->middleware(['operator'])->name('4');

            Route::prefix('{id}/events')->middleware(['auth:api', 'operator'])->name('5')->group(function () {

                Route::post('/add', 'Vehicles\CalendarController@addEvent')->name('7');

                Route::post('set-available', 'Vehicles\CalendarController@setFree')->name('6');

                Route::delete('delete/{event_id}', 'Vehicles\CalendarController@deleteEvent')->name('8');

                Route::patch('update/{event_id}', 'Vehicles\CalendarController@changeEvent')->name('9');

                Route::get('{event_id?}', 'Vehicles\CalendarController@getEvents')->name('0');

            });

        });

        Route::prefix('auth')->group(function () {

            Route::post('login', '\Modules\RestApi\Http\Controllers\AuthController@login')->middleware('guest');

            Route::get('get', '\Modules\RestApi\Http\Controllers\AuthController@getUser')->middleware('auth:api');

            Route::post('logout', '\Modules\RestApi\Http\Controllers\AuthController@logout')->middleware('auth:api');

        });

        Route::prefix('settings')->middleware(['auth:api', 'admin'])->name('settings')->group(function () {

            Route::resource('roles', 'Settings\RolesController')
                ->except(['edit', 'create']);

        });

        Route::prefix('dispatcher')->middleware(['auth:api', 'operator', 'admin_helper_access'])->group(function () {

            Route::get('leads/{id}/audits', 'Dispatcher\LeadsController@audits');

            Route::get('leads/{id}/info', 'Dispatcher\LeadsController@info');

            Route::post('leads/{id}/select-contractor', 'Dispatcher\LeadsController@selectContractor');

            Route::post('leads/{id}/create-order', 'Dispatcher\LeadsController@createOrder');

            Route::post('leads/{id}/accept-dispatcher-offer', 'Dispatcher\LeadsController@acceptDispatcherOffer');

            Route::post('leads/{id}/accept-offer', 'Dispatcher\LeadsController@acceptOffer');

            Route::post('leads/{id}/close', 'Dispatcher\LeadsController@close');

            Route::resource('leads', 'Dispatcher\LeadsController')
                ->except(['edit', 'create']);

        });


        Route::prefix('support')->middleware(['auth:api', 'contentAdmin'])->group(function () {

            Route::resource('brands', 'Support\BrandsController')
                ->except(['edit', 'show', 'create']);

            Route::resource('models', 'Support\ModelsController')
                ->except(['edit', 'create']);

            Route::prefix('units')->group(function () {

                Route::get('all', 'Support\UnitsController@getUnits');

                Route::post('add', 'Support\UnitsController@addUnit');

                Route::get('{id?}', 'Support\UnitsController@getAll');

                Route::post('{id}/update', 'Support\UnitsController@updateUnit');

                Route::delete('{id}/delete', 'Support\UnitsController@deleteUnit');

            });

            Route::prefix('documents')->group(function () {

                Route::get('{id?}', 'Support\DocumentsController@getDocuments');

                Route::post('create', 'Support\DocumentsController@create');

                Route::post('{id}/update', 'Support\DocumentsController@update');

                Route::post('upload', 'Support\DocumentsController@upload');

            });

            Route::prefix('countries')->group(function () {

                Route::get('{id?}', 'Support\CountriesController@getCountries');

                Route::post('add', 'Support\CountriesController@create');

                Route::post('{id}/update', 'Support\CountriesController@update');

                Route::get('{id}/federal-districts', 'Support\CountriesController@federalDistricts');

                Route::prefix('{id}/regions')->group(function () {

                    Route::post('add', 'Support\RegionsController@create');

                    Route::get('{region_id?}', 'Support\RegionsController@getRegions');

                    Route::post('{region_id}/update', 'Support\RegionsController@update');

                    Route::prefix('{region_id}/cities')->group(function () {

                        Route::post('add', 'Support\CitiesController@create');

                        Route::get('{city_id?}', 'Support\CitiesController@getCities');

                        Route::post('{city_id}/update', 'Support\CitiesController@update');

                    });

                });


            });

            Route::prefix('categories')->group(function () {


                Route::post('add', 'Support\SupportDirectoriesController@addCategory');

                Route::get('{id?}', 'Support\SupportDirectoriesController@getCategories');

                Route::post('{id}/update', 'Support\SupportDirectoriesController@updateCategory');

                Route::delete('{id}/delete', 'Support\SupportDirectoriesController@deleteCategory');

                Route::get('{id}/attributes', 'Support\SupportDirectoriesController@getAttributes');

                Route::post('{id}/attributes', 'Support\SupportDirectoriesController@setAttributes');

                Route::post('{id}/set-avg-market-price', 'Support\SupportDirectoriesController@setAvgMarketPrice');

            });


        });


    });
};
Route::group(['domain' => /*'api.' .*/ env('APP_ROUTE_URL')], $routes);
