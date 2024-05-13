<?php

use App\Http\Controllers\ServiceAccountController;
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

Route::group(['middleware' => 'apiauth'], function () {
    Route::post('/fetch', [ServiceAccountController::class, 'fetchData']);
    Route::post('/tables', [ServiceAccountController::class, 'fetchTables']);
    Route::post('/columns', [ServiceAccountController::class, 'fetchColumns']);
});
