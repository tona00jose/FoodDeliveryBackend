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

// auth
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
// restaurant
Route::get('/restaurants/list', [RestaurantController::class, 'getList']);                              // list
Route::get('/restaurants/item/{restaurant_id}', [RestaurantController::class, 'getItem']);              // detail
// meal
Route::get('/meals/list', [MealController::class, 'getList']);                                          // list
Route::get('/meals/item/{meal_id}', [MealController::class, 'getItem']);                                // detail

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // order
    Route::post('/orders/create', [OrderController::class, 'createOrder'])                              // create
        ->middleware('role_check:customer');                                                            // can access customer
    Route::get('/orders/list', [OrderController::class, 'getList']);                                    // list
    Route::get('/orders/order/{order_id}', [OrderController::class, 'getOrder']);                       // detail
    Route::post('/orders/updateStatus/{order_id}', [OrderController::class, 'updateStatus']);           // change order status

    //  can access only admin =====================================================================================================
    Route::middleware(['role_check:admin'])->group(function () {
        // user management
        Route::apiResource('users', UserController::class);                                             // list, create, detail, update, delete
        Route::put('/users/block/{user_id}', [UserController::class, 'blockItem']);                     // block
        // coupon management
        Route::apiResource('coupons', CouponController::class);
        // order management
        Route::apiResource('orders', OrderController::class);
        // order item management
        Route::apiResource('order/items', OrderItemController::class);
    });

    // can access admin, restaurant owner =========================================================================================
    Route::middleware(['role_check:admin_or_restaurant_owner'])->group(function () {
        // restaurant management
        Route::apiResource('restaurants', RestaurantController::class);
        Route::put('/restaurants/block/{restaurant_id}', [RestaurantController::class, 'blockItem']);   // block
        // meal management
        Route::apiResource('meals', MealController::class);
        Route::put('/meals/block/{meal_id}', [MealController::class, 'blockItem']);                        // block
    });
});







