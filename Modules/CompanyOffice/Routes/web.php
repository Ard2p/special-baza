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


use Modules\AdminOffice\Http\Controllers\InsuranceController;
use Modules\CompanyOffice\Http\Controllers\DispatcherInvoiceController;
use Modules\CompanyOffice\Http\Controllers\Googleapi\GoogleApiSettingController;


Route::prefix('rest/v1')->group(function () {


    Route::resource('companies/slang-categories',
        'Directories\SlangCategoryController')->middleware('auth:api')->except([
        'show',
        'create',
        'edit',
        'update',
    ]);

    Route::get('companies/{id}', 'CompaniesController@show');

    Route::get('companies/{id}/categories', 'CompaniesController@getAvailableCategories');

    Route::get('companies/{id}/available-cities', 'CompaniesController@getAvailableCities');

    Route::get('companies/{id}/settings', 'CompaniesController@getSettings');

    Route::prefix('insurance')->middleware('auth:api')->group(function () {
        Route::get('/export-branch-certificates',
            [InsuranceController::class, 'exportBranchCertificates'])->name('insurance.export-branch');
    });

    Route::prefix('companies')->middleware('auth:api')->group(function () {

    });

    Route::prefix('companies')->middleware('auth:api')->group(function () {

        Route::prefix('insurance')->group(function () {

            Route::get('ins_tariffs', 'Insurance\InsTariffController@show');

            Route::get('ins_setting', 'Insurance\InsSettingController@get');
            Route::resource('ins_settings', 'Insurance\InsSettingController');

            Route::post('ins_tariff_settings/save', 'Insurance\InsTariffSettingController@save');
            Route::resource('ins_tariff_settings', 'Insurance\InsTariffSettingController');

        });

        Route::prefix('clientbank')->group(function () {
            Route::get('setting', 'Clientbank\ClientBankSettingController@get');
            Route::resource('settings', 'Clientbank\ClientBankSettingController');
        });

        Route::prefix('googleapi')->group(function () {
            Route::get('auth-url', [GoogleApiSettingController::class, 'getAuthUrl']);
            Route::get('setting', [GoogleApiSettingController::class, 'get']);
            Route::delete('setting', [GoogleApiSettingController::class, 'delete']);
            Route::post('calendar', [GoogleApiSettingController::class, 'createCalendar']);
            Route::delete('calendar/{calendar}', [GoogleApiSettingController::class, 'deleteCalendar']);
        });

        Route::prefix('dispatcher')->group(function () {
            Route::get('invoices', [DispatcherInvoiceController::class, 'getInvoices']);
            Route::post('distribute-invoices/{cr}', [DispatcherInvoiceController::class, 'distributeInvoices']);
        });


        Route::get('', 'CompaniesController@index');

        Route::get('{id}/access-link', 'CompaniesController@getAccessLink');

        Route::prefix('branches')->group(function () {

            Route::get('roles', 'BranchesController@getBranchRoles');

            Route::prefix('requisites')->group(function () {

                Route::resource('legal', 'Requisites\LegalController');

                Route::resource('individual', 'Requisites\IndividualController');
            });

            Route::get('{id}', 'BranchesController@getBranch');

            Route::post('{id}', 'BranchesController@updateBranch');

            Route::post('{id}/create-invite', 'BranchesController@generateInvite');

            Route::get('{id}/employees', 'BranchesController@getEmployees');

            Route::get('{id}/tags', 'BranchesController@getTags');
            Route::post('{id}/tags', 'BranchesController@storeTag');
            Route::patch('{id}/tags/{tag_id}', 'BranchesController@editTag');
            Route::delete('{id}/tags/{tag_id}', 'BranchesController@deleteTag');

            Route::patch('{id}/employee', 'BranchesController@changeEmployee');
            Route::delete('{id}/employee/{employee_id}', 'BranchesController@detachEmployee');

            Route::post('{id}/settings', 'BranchesController@setSettings');
            Route::get('{id}/settings', 'BranchesController@getSettings');
            Route::resource('{id}/commercial-offers', 'CommercialOffersController')->except(['create', 'edit', 'show']);
            Route::resource('{id}/expenditures', 'ExpenditureController')->except(['create', 'edit', 'show']);

            Route::get('{id}/telematics/wialon',
                '\Modules\ContractorOffice\Http\Controllers\WialonController@getConnection');
            Route::post('{id}/telematics/wialon',
                '\Modules\ContractorOffice\Http\Controllers\WialonController@checkConnection');

            Route::resource('{id}/schedule', 'ScheduleController')->except(['create', 'edit', 'show', 'update']);

            Route::get('{id}/schedule/day-info', 'ScheduleController@dayInfo');

            Route::post('{id}/documents-packs/{doc}/save-template', 'DocumentsPacksController@saveTemplate');
            Route::resource('{id}/documents-packs', 'DocumentsPacksController')->except(['create', 'edit']);

            Route::resource('{id}/budget', 'BudgetController')->only(['index', 'store']);

            Route::post('{id}/cash-register/break-pay/{pay_id}', 'CashRegisterController@breakPays');

            Route::post('{id}/cash-register/employee-withdrawal', 'CashRegisterController@employeeWithdrawal');

            Route::resource('{id}/cash-register', 'CashRegisterController')->only(['index', 'store', 'destroy']);

        });


        Route::patch('{id}/settings', 'CompaniesController@updateCompanySettings');


    });

    Route::prefix('crm')->middleware('auth:api')->group(function () {

        Route::get('persons-list', 'Crm\CommunicationsController@personsList');

        Route::post('send-mail', 'Crm\CommunicationsController@sendMail');

        Route::get('employee-contacts', 'Crm\CommunicationsController@getEmployeeContacts');

        Route::get('communications', 'Crm\CommunicationsController@index');

        Route::post('communications/clip', 'Crm\CommunicationsController@clip');
        Route::post('communications/action', 'Crm\CommunicationsController@actionCalls');

        Route::patch('communications/{id}/status', 'Crm\CommunicationsController@setStatus');

        Route::get('mails', 'Crm\CommunicationsController@getMails');

        Route::post('mails/action', 'Crm\CommunicationsController@actionMails');

        Route::resource('events', 'Crm\EventsController')->except(['create', 'edit']);


        Route::prefix('mailings')->group(function () {

            Route::get('{id}/emails', 'Crm\MailingsController@getEmails');

            Route::post('{id}/emails', 'Crm\MailingsController@sendEmails');

            Route::post('{id}/clone', 'Crm\MailingsController@cloneMailing');

            Route::delete('{id}/emails', 'Crm\MailingsController@deleteEmails');

            Route::post('{id}/start', 'Crm\MailingsController@start');

        });

        Route::resource('mailings', 'Crm\MailingsController')->except(['create', 'edit']);
    });

    Route::get('companies/branches/{id}/invite-info', 'BranchesController@inviteInfo');

    Route::get('companies/branches/{id}/invite', 'BranchesController@inviteUser')->name('invite_employee');

    Route::post('companies/branches/{id}/invite', 'BranchesController@acceptInvite');

    Route::resource('branch/documents', 'CompanyDocumentsController');
});
