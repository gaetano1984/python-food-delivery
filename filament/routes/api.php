<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['prefix' => 'user'], function () {
    Route::post('login', [\App\Http\Controllers\AuthController::class, "login"]);
    Route::post('register', [\App\Http\Controllers\AuthController::class, "register"]);
});
Route::group(['prefix' => 'restaurants', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list', [\App\Http\Controllers\RestaurantController::class, "list"]);
    Route::get('/{id}/menu', [\App\Http\Controllers\RestaurantController::class, "getMenu"]);
});
Route::group(['prefix' => 'order', 'middleware' => 'auth:sanctum'], function(){
    Route::post('place', [\App\Http\Controllers\RestaurantController::class, "placeOrder"]);
});
