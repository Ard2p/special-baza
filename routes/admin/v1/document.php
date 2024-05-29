<?php

use Modules\Dispatcher\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::name('document.')->group(function () {
    Route::post('/generate-сontract/{id}', [DocumentController::class, 'generateContract'])->name('generate-contract');
});