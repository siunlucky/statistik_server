<?php

use App\Http\Controllers\MasterControllerApi;
use App\Http\Controllers\VisitorControllerApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::prefix('master')->group(function(){
//     Route::get('/halaman', function(){
//         return 'tes';
//     });
// });