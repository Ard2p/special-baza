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


use Modules\ContractorOffice\Http\Controllers\Scoring\ScoringController;

Route::prefix('rest/v1')->group(function () {
    Route::middleware(['auth:api'])->group(function () {
        Route::get('/scoring', [ScoringController::class, 'show'])->name('scoring.show');
    });

    Route::get('user/contractor/vehicles/models', 'VehiclesController@getModels');

    Route::prefix('helpers')->group(function () {

        Route::get('create-worker', 'WorkersController@getInitial');

        Route::post('update-person/{id}', 'ContractorOfficeController@updatePerson')->middleware(['auth:api']);

        Route::get('find-persons', 'ContractorOfficeController@findPersons')->middleware(['auth:api']);
        Route::get('find-customers', 'ContractorOfficeController@findCustomers')->middleware(['auth:api']);
        Route::get('find-contractors', 'ContractorOfficeController@findContractors')->middleware(['auth:api']);

    });
    Route::prefix('user/contractor')->middleware(['auth:api'])->group(function () {

        Route::get('calendar/events', 'CalendarController@getCountCalendarEvents');

        Route::post('order', 'OrdersController@createFastOrder');

        Route::resource('services', 'ServicesController')->except(['create', 'edit']);

        Route::get('workers/available', 'WorkersController@getAvailable');

        Route::get('machinery/available', 'VehiclesController@getAvailable');

        Route::resource('workers', 'WorkersController')->except(['create', 'edit']);

        Route::prefix('workers')->group(function () {

            Route::get('{id}/machinery', 'WorkersController@getMachinery');

            Route::get('{id}/events', 'WorkersController@getEvents');

            Route::post('{id}/machinery', 'WorkersController@attachMachinery');

            Route::delete('{id}/machinery', 'WorkersController@detachMachinery');


        });

        Route::prefix('sets')->group(function () {

            Route::get('search', 'MachinerySetController@search');
            Route::get('scaffolding', 'MachinerySetController@scaffolding');

            Route::resource('machinery-sets', 'MachinerySetController')->except(['create', 'edit']);


        });
        Route::get('main-categories', 'ContractorOfficeController@mainCategories');

        Route::prefix('tariffs')->group(function () {

            Route::resource('units-compare', 'System\TariffUnitsCompareController')->only(['store', 'index']);
        });

        Route::prefix('stats')->group(function () {

            Route::get('get', 'ContractorOfficeController@getStats');

            Route::get('telematic', 'ContractorOfficeController@getTelematicStats');

            Route::get('orders', 'ContractorOfficeController@getOrderStats');

            Route::get('calendar', 'ContractorOfficeController@getCalendarStats');
            Route::get('calendar2', 'ContractorOfficeController@getCalendarStats2');

            Route::get('dashboard', 'ContractorOfficeController@getAttentionData');

            Route::get('utilization', 'ContractorOfficeController@getUtilizationV2');

            Route::get('reject', 'ContractorOfficeController@getRejectStats');

            Route::get('warehouse', 'ContractorOfficeController@getWarehouseReport');
            Route::get('report', 'ContractorOfficeController@getReport');
            Route::get('cashbox', 'ContractorOfficeController@getCashBox');
            Route::get('full-report', 'ContractorOfficeController@getFullReport');
            Route::get('sub-contractors-excel', 'ContractorOfficeController@subContractorsExcel');
            Route::get('invoices', 'ContractorOfficeController@getInvoices');
        });

        Route::prefix('filters')->group(function () {

            Route::get('vehicles', 'VehiclesController@getVehiclesFilters');

            Route::get('orders', 'VehiclesController@getOrdersFilters');
        });

        Route::resource('machinery-bases', 'MachineryBasesController')->except(['edit', 'create']);

        Route::get('machinery-bases/{id}/types', 'MachineryBasesController@types');

        Route::resource('drivers', 'DriverController')->except(['edit', 'create']);

        Route::prefix('vehicles')->group(function () {

            Route::prefix('shop')->group(function () {

                Route::resource('purchase', 'MachineryShop\PurchaseController')->except(['edit', 'create']);

                Route::resource('sales', 'MachineryShop\SalesController')->except(['edit', 'create']);

                Route::resource('sale-requests', 'MachineryShop\SaleRequestsController')->except(['edit', 'create']);

                Route::prefix('sale-requests')->group(function () {

                    Route::post('{id}/sale', 'MachineryShop\SaleRequestsController@sale');

                    Route::get('{id}/contract', 'MachineryShop\SaleRequestsController@getContract');

                    Route::delete('{id}/contract', 'MachineryShop\SaleRequestsController@deleteContract');

                    Route::post('{id}/contract', 'MachineryShop\SaleRequestsController@addContract');

                });
                Route::prefix('sales')->group(function () {

                    Route::get('{id}/application/{operationId}', 'MachineryShop\SalesController@getApplication');

                    Route::get('{id}/contract', 'MachineryShop\SalesController@getContract');

                    Route::get('{id}/documents', 'MachineryShop\SalesController@getDocuments');

                });


            });

            Route::resource('{id}/services', 'MachineryServicesController')->except(['edit', 'create']);

            Route::get('/search', 'VehiclesController@searchVehicles');

            Route::get('/optional-attributes', 'VehiclesController@getOptionalAttributes');

            Route::post('/import', 'VehiclesController@import');

            Route::get('{id}/documents', 'VehiclesController@getDocuments');
            Route::post('{id}/documents', 'VehiclesController@updateDocuments');
            Route::delete('{id}/documents', 'VehiclesController@deleteDocuments');

            Route::get('/tariffs', 'VehiclesController@getTariffs');

            Route::get('scaffoldings', 'VehiclesController@getScaffoldings');

            Route::get('{id?}', 'VehiclesController@getVehicles');

            Route::post('add', 'VehiclesController@addVehicle');

            Route::post('import', 'VehiclesController@importVehicles');

            Route::post('{id}/update', 'VehiclesController@updateVehicle');

            Route::delete('{id}/delete', 'VehiclesController@deleteVehicle');

            Route::post('{id}/set-rent-status', 'VehiclesController@setRentedStatus');

            Route::get('{id}/work-hours', 'WorkHoursController@getWorkHours');

            Route::put('{id}/work-hours', 'WorkHoursController@updateWorkHours');

            Route::get('{id}/orders', 'VehiclesController@getVehicleOrders');

            Route::get('{id}/leads', 'VehiclesController@getVehicleLeads');

            Route::get('{id}/assessments', 'VehiclesController@getAssessments');
            Route::get('{id}/actual-cost', 'VehiclesController@getActualCost');
            Route::get('{id}/actual-delivery-cost', 'VehiclesController@getActualDeliveryCost');

            Route::get('{id}/available-dates', 'VehiclesController@getAvailableDates');
            Route::get('{id}/available-duration', 'VehiclesController@getAvailableDuration');

            Route::post('{id}/tariff', 'VehiclesController@postTariff');
            Route::post('{id}/delivery-tariff', 'VehiclesController@setDeliveryPrices');

            Route::prefix('{id}/events')->group(function () {

                Route::post('/add', 'CalendarController@addEvent');

                Route::get('/all', 'CalendarController@getCalendarEvents');

                Route::post('set-available', 'CalendarController@setFree');

                Route::delete('{event_id}', 'CalendarController@deleteEvent');

                Route::patch('{event_id}', 'CalendarController@changeEvent');

                Route::get('{event_id?}', 'CalendarController@getEvents');


            });


        });


        Route::get('orders/{id?}', 'OrdersController@getOrders');

        Route::post('orders/{id}/add-timestamp', 'OrdersController@addMachineryTimestamp');

        Route::post('orders/{id}/prolongation', 'OrdersController@prolongation');
        Route::post('orders/{id}/return-parts', 'OrdersController@returnParts');
        Route::post('orders/{id}/add-position', 'OrdersController@addPosition');

        Route::post('orders/{id}/change-customer', 'OrdersController@changeCustomer');
        Route::post('orders/{id}/change-contract', 'OrdersController@changeContract');
        Route::post('orders/{id}/change-contractor', 'OrdersController@changeContractor');
        Route::post('orders/{id}/change-driver', 'OrdersController@changeDriver');
        Route::post('orders/{order}/avito-sync', 'OrdersController@avitoSync');
        Route::post('orders/{id}/change-principal', 'OrdersController@changePrincipal');

        Route::patch('orders/{id}/sets', 'OrdersController@upgradeSets');
        Route::post('orders/{id}/idle', 'OrdersController@idle');
        Route::post('orders/{id}/update-position', 'OrdersController@updatePosition');
        Route::post('orders/{id}/update-avito-ad-sum', 'OrdersController@updateAvitoAdSum');
        Route::post('orders/{id}/update-avito-dotation-sum', 'OrdersController@updateAvitoDotationSum');

        Route::patch('orders/{id}/application', 'OrdersController@changeApplication');

        Route::get('orders/{id}/actual-hours', 'OrdersController@getActualHours');

        Route::post('orders/{id}/actual', 'OrdersController@saveActualApplication');
        Route::delete('orders/{id}/actual', 'OrdersController@deleteActualApplication');

        Route::post('orders/{id}/reject', 'OrdersController@rejectApplication');

        Route::post('orders/{id}/contractor-feedback', 'OrdersController@contractorFeedback');

        Route::post('orders/{id}/upload-doc', 'OrdersController@uploadDoc');

        Route::get('orders/{id}/documents', 'OrdersController@getDocuments');

        Route::prefix('finance')->group(function () {

            Route::get('transactions', 'FinanceController@getTransactions');

            Route::get('balance-histories', 'FinanceController@getBalanceHistories');

            Route::post('withdraw', 'FinanceController@withdraw');

        });

        Route::prefix('wialon')->group(function () {

            Route::post('check-connection', 'WialonController@checkConnection');

            Route::get('vehicles', 'WialonController@getVehicles');

        });


    });
});
