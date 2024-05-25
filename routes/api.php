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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::post('login/auth', [AuthController::class, 'login'])->name('login');
Route::post('logout/auth', [AuthController::class, 'logout']);
// Route::middleware('authenticate')->group(function () {
Route::get('/verify-token/{token}', [AuthController::class, 'verifyToken']);
Route::get('/data-view', [DataViewController::class, 'dataView']);

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('client-management')->group(function () {
        Route::post('/list/view', [ClientManagementController::class, 'dataView']);
        Route::post('/list/add', [ClientManagementController::class, 'dataAdd']);
        Route::post('/list/edit', [ClientManagementController::class, 'dataEdit']);
        Route::post('/list/delete', [ClientManagementController::class, 'dataDelete']);
        Route::post('/list/status', [ClientManagementController::class, 'dataStatus']);

        Route::post('/sales/view', [ClientManagementController::class, 'salesView']);
        Route::post('/sales/add', [ClientManagementController::class, 'salesAdd']);
        Route::post('/sales/edit', [ClientManagementController::class, 'salesEdit']);
        Route::post('/sales/delete', [ClientManagementController::class, 'salesDelete']);

        Route::post('/progress/view', [ClientManagementController::class, 'progressView']);
        Route::post('/progress/add', [ClientManagementController::class, 'progressAdd']);
        Route::post('/progress/edit', [ClientManagementController::class, 'progressEdit']);
        Route::post('/progress/delete', [ClientManagementController::class, 'progressDelete']);

        Route::post('/success/view', [ClientManagementController::class, 'successView']);
        Route::post('/success/add', [ClientManagementController::class, 'successAdd']);
        Route::post('/success/edit', [ClientManagementController::class, 'successEdit']);
        Route::post('/success/delete', [ClientManagementController::class, 'successDelete']);

        Route::post('/attendance/view', [ClientManagementController::class, 'attendanceView']);

        Route::post('/btm/view', [ClientManagementController::class, 'btmView']);
        Route::post('/btm/add', [ClientManagementController::class, 'btmAdd']);
        Route::post('/btm/delete', [ClientManagementController::class, 'btmDelete']);

        Route::post('/photo/view', [ClientManagementController::class, 'photoView']);
        Route::post('/photo/add', [ClientManagementController::class, 'photoAdd']);

        Route::post('/dashboard/view', [ClientManagementController::class, 'dashboardView']);
    });
    Route::prefix('preference')->group(function () {
        Route::post('/scheduler/view', [PreferenceController::class, 'schedulerView']);
        Route::post('/scheduler/add', [PreferenceController::class, 'schedulerAdd']);
        Route::post('/scheduler/edit', [PreferenceController::class, 'schedulerEdit']);
        Route::post('/scheduler/delete', [PreferenceController::class, 'schedulerDelete']);

        Route::post('/access/view', [PreferenceController::class, 'accessView']);
        Route::post('/access/add', [PreferenceController::class, 'accessAdd']);
        Route::post('/access/edit', [PreferenceController::class, 'accessEdit']);
        Route::post('/access/delete', [PreferenceController::class, 'accessDelete']);

        Route::post('/package/view', [PreferenceController::class, 'packageView']);
        Route::post('/package/add', [PreferenceController::class, 'packageAdd']);
        Route::post('/package/edit', [PreferenceController::class, 'packageEdit']);
        Route::post('/package/delete', [PreferenceController::class, 'packageDelete']);

        Route::post('/class/view', [PreferenceController::class, 'classView']);
        Route::post('/class/add', [PreferenceController::class, 'classAdd']);
        Route::post('/class/edit', [PreferenceController::class, 'classEdit']);
        Route::post('/class/delete', [PreferenceController::class, 'classDelete']);

        Route::post('/event/view', [PreferenceController::class, 'eventView']);
        Route::post('/event/add', [PreferenceController::class, 'eventAdd']);
        Route::post('/event/edit', [PreferenceController::class, 'eventEdit']);
        Route::post('/event/delete', [PreferenceController::class, 'eventDelete']);
    });
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
        Route::post('/add', [ProspectController::class, 'listAdd']);
        Route::post('/edit', [ProspectController::class, 'listEdit']);
        Route::post('/delete', [ProspectController::class, 'listDelete']);
    });
});
// });
