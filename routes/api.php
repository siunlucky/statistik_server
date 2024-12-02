<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureTokenIsValid;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\MasterControllerApi;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\VisitorControllerApi;
use App\Http\Controllers\Api\VisitorController;
use App\Http\Controllers\Api\ServerStatusController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('master')->group(function(){
    Route::get('/halaman', [MasterControllerApi::class, 'get']);
    Route::post('/tambah-halaman', [MasterControllerApi::class, 'add_data']);
});

Route::prefix('visitor')->group(function(){
    Route::get('/req-id-guest', [VisitorControllerApi::class, 'req_id_guest']);
    Route::post('/add', [VisitorControllerApi::class, 'add_visitor']);
    Route::get('/', [VisitorControllerApi::class, 'get']);
    Route::post('/record-registrasi', [VisitorControllerApi::class, 'record_registrasi']);
});

Route::get('test/{id}', function($id){
    return $id;
});

Route::prefix('auth')->group(function () {
    Route::group(['middleware' => [EnsureTokenIsValid::class]], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

Route::middleware([EnsureTokenIsValid::class])->group(function () {
    Route::prefix('server-status')->group(function () {
        Route::get('/', [ServerStatusController::class, 'index']);
    });

    Route::prefix('graph')->group(function () {
        Route::prefix('/users')->group(function () {
            Route::get('/', [MemberController::class, 'users']);
            Route::get('/new-users', [MemberController::class, 'new_users']);
            Route::get('/user-growth', [MemberController::class, 'user_growth']);

            Route::get('/total-verified-email', [MemberController::class, 'total_verified_email']);
        });
    });
});
