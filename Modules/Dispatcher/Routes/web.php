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


use Modules\Dispatcher\Http\Controllers\DocumentController;
use Modules\Dispatcher\Http\Controllers\DispatcherOrdersController;

$routes = function (){

    Route::prefix('rest/v1/dispatcher')->group(function(){

        Route::get('helpers/reject-reasons', 'HelperController@rejectReasons');


        Route::prefix('fssp')->middleware('auth:api')->group(function(){
            Route::post('search', 'FsspController@search');
            Route::get('result', 'FsspController@result');
        });

        Route::prefix('helpers')->middleware('auth:api')->group(function(){

            Route::get('create-vehicle', 'VehiclesController@createHelper');

            Route::get('create-contractor', 'ContractorsController@createHelper');

            Route::get('create-lead', 'LeadsController@createHelper');



            Route::get('search-city/{id?}', '\Modules\RestApi\Http\Controllers\HelpersController@searchCity');

            Route::get('search-emails/', 'CustomersController@searchByEmails');
        });

        Route::prefix('filters')->middleware('auth:api')->group(function(){

            Route::get('vehicles', 'VehiclesController@getFilters');

            Route::get('contractors', 'VehiclesController@getFilters');

            Route::get('customers', 'VehiclesController@getFilters');
        });

        Route::prefix('settings')->middleware('auth:api')->group(function(){

            Route::get('ya-call', 'DispatcherController@getYandexPhoneAccount');

            Route::post('ya-call', 'DispatcherController@yandexPhoneAccount');


        });

        Route::middleware('auth:api')->group(function (){

            Route::prefix('contractors/requisites')->group(function (){

                Route::resource('bank', 'Requisites\BankRequisitesController')
                    ->only('destroy');

                Route::resource('entity', 'Requisites\EntityController')->except(['create', 'edit']);

                Route::resource('individual', 'Requisites\IndividualController')->except(['create', 'edit']);
            });

            Route::resource('contractors', 'ContractorsController')->except(['create', 'edit']);

            Route::get('contractors/{id}/info', 'ContractorsController@getInfo');

            Route::post('contractors/{id}/settings', 'ContractorsController@setSettings');


            Route::prefix('customers')->group(function (){

                Route::post('/generate-contract/{id}', [DocumentController::class, 'generateContract'])->name('generate-contract');
                Route::post('/download-contract/{id}', [DocumentController::class, 'downloadContract'])->name('download-contract');

                Route::get('contracts', 'CustomersController@getContracts');
                Route::delete('contracts/{id}', 'CustomersController@deleteContract');

                Route::post('import', 'CustomersController@import');

                Route::post('{id}/remove-duplicate', 'CustomersController@removeDuplicate');

                Route::get('{id}/info', 'CustomersController@getInfo');

                Route::post('{id}/contacts', 'CustomersController@addContacts');
                Route::delete('{id}/contacts/{contact_id}', 'CustomersController@deleteContact');

                Route::get('{id}/comments', 'CustomersController@getComments');

                Route::post('{id}/comments', 'CustomersController@postComment');

                Route::post('{id}/tags', 'CustomersController@setTags');

                Route::post('{id}/settings', 'CustomersController@setSettings');

                Route::delete('{id}/comments/{comment_id}', 'CustomersController@deleteComment');

                Route::get('{id}/calls-history', 'CustomersController@callsHistory');

                Route::prefix('requisites')->group(function () {

                    Route::resource('entity', 'Requisites\CustomerEntityController')->except(['create', 'edit']);

                    Route::resource('individual', 'Requisites\CustomerIndividualController')->except(['create', 'edit']);

                });

                Route::prefix('{id}/corp-cabinet')->group(function () {

                    Route::get('users', 'CorpCabinetController@getUsers');
                    Route::delete('users/{user_id}', 'CorpCabinetController@detachUser');
                    Route::post('invite', 'CorpCabinetController@inviteUser');
                });

            });

            Route::resource('customers', 'CustomersController')->except(['create', 'edit']);

            Route::resource('vehicles', 'VehiclesController')->except(['create', 'edit']);

            Route::resource('leads', 'LeadsController')->except(['create', 'edit']);


            Route::prefix('leads')->group(function (){

                Route::post('{id}/merge-pdf', 'LeadsController@mergePdf');

                Route::post('{id}/invoice', 'LeadsController@generateInvoice');

                Route::get('{id}/info', 'LeadsController@info');

                Route::post('{id}/select-contractor', 'LeadsController@selectContractor');

                Route::post('{id}/create-order', 'LeadsController@createOrder');

                Route::post('{id}/cancel-order', 'LeadsController@cancelOrder');

                Route::post('{id}/create-offer', 'LeadsController@createOffer');

                Route::patch('{id}/change', 'LeadsController@setSettings');
                Route::patch('{id}/name', 'LeadsController@updateName');
                Route::patch('{id}/position/{positionId}', 'LeadsController@updatePositionField');

                Route::post('{id}/send-document', '\Modules\Orders\Http\Controllers\OrdersController@sendDocument');

                Route::patch('{id}/manager', 'LeadsController@changeManager');

                Route::patch('{id}/change-publish-type', 'LeadsController@changePublishType');

                Route::post('{id}/accept-offer', 'LeadsController@acceptOffer');

                Route::post('{id}/accept-dispatcher-offer', 'LeadsController@acceptDispatcherOffer');

                Route::get('{id}/contract', 'ContractsController@getContract');

                Route::get('{id}/positions/{position_id}/machineries', 'LeadsController@getAvailableMachineries');

                Route::get('{id}/form-contract', 'ContractsController@formContract');
                Route::post('{id}/contract', 'ContractsController@addContract');
                Route::delete('{id}/contract', 'ContractsController@deleteContract');

                Route::get('{id}/documents', 'LeadsController@getDocuments');
            });

            Route::resource('pre-leads', 'PreLeadsController')->except(['create', 'edit']);

            Route::prefix('pre-leads')->group(function () {

                Route::patch('{id}/transform', 'PreLeadsController@transform');

                Route::patch('{id}/reject', 'PreLeadsController@reject');

            });

            Route::prefix('orders')->group(function () {

                Route::post('{id}/complete', 'DispatcherOrdersController@complete');

                Route::post('{id}/paid', 'DispatcherOrdersController@paid');

                Route::post('{id}/change-amount', 'DispatcherOrdersController@changeAmount');

                Route::post('{id}/upload-doc', 'DispatcherOrdersController@uploadDoc');

                Route::get('{id}/documents', 'DispatcherOrdersController@getDocuments');

                Route::post('{order}/merge-pdf', [DispatcherOrdersController::class,'mergePdf']);

            });


            Route::prefix('invoices/{invoice_id}')->group(function () {

                Route::resource('pays', 'PaysController')->except(['create', 'edit']);

                Route::post('sync-onec', 'InvoiceController@syncOneC');
                Route::post('release', 'InvoiceController@releaseServices');
                Route::post('update-paid', 'InvoiceController@updatePaidDate');

                Route::get('cash-order', 'PaysController@downloadCashOrder');

            });

            Route::prefix('documents')->group(function () {
                Route::post('commercial-offer', 'LeadsController@getCommercialOffer');
            });

            Route::get('requisites-for-invoice', 'DispatcherOrdersController@requitesForInvoice');

            Route::post('invoices/service-center', 'InvoiceController@addServiceInvoice');

            Route::resource('invoices', 'InvoiceController')->except(['create', 'edit']);

            Route::resource('contractor-pays', 'ContractorPaysController')->except(['create', 'edit']);

            Route::resource('orders', 'DispatcherOrdersController')->except(['create', 'edit']);

        });

        Route::any('orders/invoices/{alias}/pdf', 'InvoiceGeneratorController@getPdf')->name('dispatcher_invoice_link');

        Route::get('orders/{id}/details', 'InvoiceGeneratorController@getPdfDetails')->name('dispatcher_order_pdf_details');
    });

    Route::prefix('rest/v1/corp-customer/{id}')->middleware('auth:api')->group(function(){

        Route::get('orders/{order_id}/contract', 'CorpCabinet\OrdersController@getContract');
        Route::get('orders/{order_id}/documents', 'CorpCabinet\OrdersController@getDocuments');

        Route::resource('proposals', 'CorpCabinet\ProposalsController');

        Route::resource('orders', 'CorpCabinet\OrdersController');

    });
};




Route::group(['domain' => /*'api.' .*/ env('APP_ROUTE_URL')], $routes);
