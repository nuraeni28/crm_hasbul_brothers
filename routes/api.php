<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FrontDeskController;
use App\Http\Controllers\ProspectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('login/auth', [AuthController::class, 'login']);
Route::post('logout/auth', [AuthController::class, 'logout']);
// Route::middleware('authenticate')->group(function () {
Route::get('/verify-token/{token}', [AuthController::class, 'verifyToken']);
Route::get('/data-view', [DataViewController::class, 'dataView']);
Route::prefix('user')->group(function () {
    Route::post('/list/view', [UserController::class, 'listUserView']);
    Route::post('/list/edit', [UserController::class, 'listUserEdit']);
    Route::post('/list/add', [UserController::class, 'listUserAdd']);
    Route::post('/list/delete', [UserController::class, 'listUserDelete']);
});

Route::prefix('frontdesk/attendance')->group(function () {
    Route::post('/view', [FrontDeskController::class, 'listView']);
    Route::post('/add', [FrontDeskController::class, 'listAdd']);
    Route::post('/edit', [FrontDeskController::class, 'listEdit']);
});
Route::prefix('prospect-management/list')->group(function () {
    Route::post('/view', [ProspectController::class, 'listView']);
});
// });
