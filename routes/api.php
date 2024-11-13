<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MasterControllerApi;
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


Route::prefix('server-status')->group(function () {
    Route::get('/', [ServerStatusController::class, 'index']);
});

Route::prefix('graph')->group(function () {
    Route::get('/visitor', [VisitorController::class, 'visitor']);
    Route::prefix('/users')->group(function () {
        Route::get('/new-users', [VisitorController::class, 'new_users']);
        Route::get('/returning-users', [VisitorController::class, 'returning_users']);
        Route::prefix('/returning-users')->group(function () {
            Route::get('/daily', [VisitorController::class, 'returning_users_daily']);
            Route::get('/weekly', [VisitorController::class, 'returning_users_weekly']);
            Route::get('/monthly', [VisitorController::class, 'returning_users_monthly']);
            Route::get('/yearly', [VisitorController::class, 'returning_users_yearly']);
        });
    });

});

Route::get('/test', function(){
    DB::connection()->getPDO();
    try {
        DB::connection()->getPDO();
        return DB::connection()->getDatabaseName();
        } catch (\Exception $e) {
        return 'None';
    }
});
// Route::prefix('master')->group(function(){
//     Route::get('/halaman', function(){
//         return 'tes';
//     });
// });
