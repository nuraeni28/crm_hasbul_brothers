<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
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
    Route::post('/user/list/view', [UserController::class, 'listUserView']);
    Route::post('/user/list/edit', [UserController::class, 'listUserEdit']);
// });
