<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\MealController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderItemController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // can access admin
    Route::group([ 'prefix' => 'admin', 'middleware' => ['role_check:admin'] ], function () {
        // user management
        Route::apiResource('users', UserController::class);
        Route::put('/users/block/{user}', [UserController::class, 'blockItem']);                        // block
        // coupon management
        Route::apiResource('coupons', CouponController::class);
    });

    // restaurant
    Route::get('/restaurants', [RestaurantController::class, 'index']);                                 // list
    Route::get('/restaurants/{restaurant}', [RestaurantController::class, 'show']);                     // detail
    // meal
    Route::get('/meals', [MealController::class, 'index']);                                             // list
    Route::get('/meals/{meal}', [MealController::class, 'show']);                                       // detail

    // can access admin, restaurant owner
    Route::middleware(['role_check:admin_or_restaurant_owner'])->group(function () {
        // restaurant
        Route::post('/restaurants', [RestaurantController::class, 'store']);                            // create
        Route::put('/restaurants/{restaurant}', [RestaurantController::class, 'update']);               // update
        Route::delete('/restaurants/{restaurant}', [RestaurantController::class, 'destroy']);           // delete
        Route::put('/restaurants/block/{restaurant}', [RestaurantController::class, 'blockItem']);      // block

        // meal
        Route::post('/meals', [MealController::class, 'store']);                                        // create
        Route::put('/meals/{meal}', [MealController::class, 'update']);                                 // update
        Route::delete('/meals/{meal}', [MealController::class, 'destroy']);                             // delete
        Route::put('/meals/block/{meal}', [MealController::class, 'blockItem']);                        // block
    });

    // order
    Route::post('/orders', [OrderController::class, 'store'])                               // create
            ->middleware('role_check:admin_or_customer');                                   // can access admin or customer
    Route::get('/orders', [OrderController::class, 'index']);                               // list
    Route::get('/orders/{order}', [OrderController::class, 'show']);                        // detail
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])                   // delete
            ->middleware('role_check:admin');                                               // can access admin
    Route::post('/orders/updateStatus/{order}', [OrderController::class, 'updateStatus']);  // change order status

});







