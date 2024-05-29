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

        Route::prefix('filters')->group(function () {

            Route::get('orders', 'OrdersController@getFilters')->middleware('auth:api');

        });

        Route::post('payments', 'PaymentsController@payment');

        Route::prefix('payments')->group(function () {

            Route::get('verify/{id}', 'PaymentsController@verify');

           /* Route::post('generate', 'PaymentsController@payment');*/

            Route::post('tinkoff-postback', 'PaymentsController@acceptPayment');

            Route::get('invoice/{alias}', 'PaymentsController@getInvoice')->name('order_invoice');

            Route::get('{id}/summary', 'PaymentsController@getSummary')->name('order_summary');
        });

        Route::middleware('auth:api')->group(function (){

            Route::prefix('service-center')->group(function () {
                Route::post('{id}/merge-pdf', 'ServiceCenterController@mergePdf');

                Route::resource('works', 'ServiceWorksController');
                Route::post('{id}/clip', 'ServiceCenterController@clipOrderComponent');

                Route::delete('{id}/unclip', 'ServiceCenterController@unClipOrderComponent');

                Route::get('{id}/works', 'ServiceCenterController@getWorks');

                Route::post('{id}/works', 'ServiceCenterController@addWorks');

                Route::delete('{id}/works', 'ServiceCenterController@removeWorks');

                Route::get('{id}/parts', 'ServiceCenterController@getParts');

                Route::post('{id}/parts', 'ServiceCenterController@attachPart');

                Route::post('{id}/parts-update', 'ServiceCenterController@updatePart');

                Route::delete('{id}/parts', 'ServiceCenterController@detachPart');

                Route::any('{id}/documents', 'ServiceCenterController@getDocuments');
                Route::any('{id}/act', 'ServiceCenterController@getApplication');
                Route::any('{id}/contract', 'ServiceCenterController@getContract');
                Route::any('{id}/return-act', 'ServiceCenterController@getReturnAct');
                Route::any('{id}/act-services', 'ServiceCenterController@getServicesAct');

                Route::post('{id}/change-contract', 'ServiceCenterController@changeContract');
            });

            Route::resource('service-center', 'ServiceCenterController')->except(['create', 'edit']);

        });


        Route::prefix('user')->middleware('auth:api')->group(function () {


            Route::get('orders/{id?}', 'OrdersController@getOrders');

            Route::post('orders/{id}/cancel', 'OrdersController@cancelHold');

            Route::patch('orders/{id}/address', 'OrdersController@changeAddress');

            Route::post('orders/{id}/delete', 'OrdersController@cancelOrder');

            Route::post('orders/{id}/done', 'OrdersController@doneOrder');

            Route::post('orders/{id}/upload-doc', 'OrdersController@uploadDoc');

            Route::get('orders/{id}/documents', 'OrdersController@getDocuments');

            Route::any('orders/{id}/contract', 'OrdersController@getContract');

            Route::post('orders/{id}/contacts', 'OrdersController@addContact');

            Route::post('orders/{id}/name', 'OrdersController@updateOrderName');

            Route::patch('orders/{id}/manager', 'OrdersController@changeManager');

            Route::delete('orders/{id}/contacts/{contact_id}', 'OrdersController@deleteContact');

            Route::post('orders/{id}/customer-feedback', 'OrdersController@customerFeedback');

            Route::post('orders/{id}/single-act', 'OrdersController@getSingleAct');

            Route::get('orders/{id}/ins-certificate', 'OrdersController@getInsCertificate');
            Route::get('orders/{id}/get-certificates', 'OrdersController@getCertificates');

            Route::get('orders/{id}/return-act', 'OrdersController@getReturnAct');
            Route::get('orders/{id}/disagreement-act', 'OrdersController@getDisagreementAct');

            Route::get('orders/{id}/application', 'OrdersController@generateApplication');

            Route::post('orders/{id}/send-document', 'OrdersController@sendDocument');

            Route::get('orders/{id}/acceptance-report', 'OrdersController@getAcceptanceReportAct');

            Route::get('orders/{id}/set-application', 'OrdersController@getSetApplication');

            Route::get('orders/{id}/set-act', 'OrdersController@getSetAct');
            Route::get('orders/{id}/set-return-act', 'OrdersController@getSetReturnAct');

            Route::get('orders/{id}/position/{position_id}/vehicles', 'OrdersController@getVehiclesForPosition');

            Route::post('orders/{id}/position/{position_id}/complete', 'OrdersController@completePosition');
            Route::post('orders/{id}/position/{position_id}/set-status', 'OrdersController@setStatus');

            Route::post('orders/{id}/position/{position_id}/return', 'OrdersController@returnToWork');
            Route::post('orders/{id}/position/{position_id}/udp', 'OrdersController@changeUdp');

            Route::get('orders/{id}/position/{position_id}/reports', 'OrdersController@getReports');
            Route::post('orders/{id}/position/{position_id}/reports', 'OrdersController@updateReport');

            Route::post('orders/{id}/position/{position_id}/vehicles/replace', 'OrdersController@replaceVehicleInPosition');


        });
    });
};
Route::group(['domain' => /*'api.' .*/ env('APP_ROUTE_URL')], $routes);
