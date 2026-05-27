<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use App\Http\Resources\OrderResource;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Order;
use App\Models\User;
use App\Models\Meal;
use App\Models\Coupon;

use Illuminate\Validation\Rule;

class OrderController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // validation check ===================================================================
        $rules = [
            'tip_amount'        => 'nullable|numeric|min:0',
            'items'             => 'required|array|min:1',
            'items.*.quantity'  => 'required|integer|min:1',
        ];
        // 'restaurant_id'     => 'required|integer|exists:restaurants,id'
        $restaurantRule = Rule::exists('restaurants', 'id')
            ->where(function ($query) {
                $query->whereNull('restaurants.deleted_at')
                    ->where('restaurants.is_blocked', 0)
                    // restaurant owner
                    ->whereExists(function ($userQuery) {
                        $userQuery->select(DB::raw(1))
                            ->from('users')
                            ->whereColumn('users.id', 'restaurants.user_id')
                            ->where('users.role', 1)
                            ->where('users.is_blocked', 0)
                            ->whereNull('users.deleted_at');
                    });
            });
        $rules['restaurant_id'] = ['required', 'integer', $restaurantRule];

        // 'items.*.meal_id'   => 'required|integer|exists:meals,id',
        $mealRule = Rule::exists('meals', 'id')
            ->where(function ($query) use ($request) {
                // meal
                $query->where('meals.is_blocked', 0)
                    ->whereNull('meals.deleted_at')
                    // IMPORTANT:
                    ->where('meals.restaurant_id', $request->restaurant_id)
                    // restaurant
                    ->whereExists(function ($restaurantQuery) {
                        $restaurantQuery->select(DB::raw(1))
                            ->from('restaurants')
                            ->whereColumn('restaurants.id', 'meals.restaurant_id')
                            ->where('restaurants.is_blocked', 0)
                            ->whereNull('restaurants.deleted_at');
                    })
                    // restaurant owner
                    ->whereExists(function ($userQuery) {
                        $userQuery->select(DB::raw(1))
                            ->from('users')
                            ->join('restaurants', 'restaurants.user_id', '=', 'users.id')
                            ->whereColumn('restaurants.id', 'meals.restaurant_id')
                            ->where('users.role', 1)
                            ->where('users.is_blocked', 0)
                            ->whereNull('users.deleted_at');
                    });
            });
        $rules['items.*.meal_id'] = ['required', 'integer', $mealRule];

        // 'coupon_code'       => 'nullable|string|max:255|exists:coupons,code',
        $couponCodeRule = Rule::exists('coupons', 'code')
            ->where(function ($query) {
                $query->where('is_active', 1)
                    ->whereNull('deleted_at')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });
            });
        $rules['coupon_code'] = ['nullable', 'string', 'max:255', $couponCodeRule];
        
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }

        // Order ===============================================================================
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $subtotal = 0;
            // Create order first
            $order = Order::create([
                'user_id'         => $user->id,
                'restaurant_id'   => $request->restaurant_id,
                'tip_amount'      => $request->tip_amount ?? 0,
                'subtotal'        => 0,
                'discount_amount' => 0,
                'total_amount'    => 0,
                'status'          => Order::STATUS_PLACED,
                'ordered_at'      => now(),
            ]);

            // Create order items
            foreach ($request->items as $item) {
                $meal = Meal::findOrFail($item['meal_id']);
                $itemSubtotal = $meal->price * $item['quantity'];
                $subtotal += $itemSubtotal;
                // automatically inserted order_id
                $order->orderItems()->create([
                    'meal_id'             => $meal->id,
                    'quantity'            => $item['quantity'],
                    'price_at_order_time' => $meal->price,
                    'subtotal'            => $itemSubtotal,
                ]);
            }

            // Coupon check
            $discountAmount = 0;
            $couponId = null;
            if ($request->coupon_code) {
                $coupon = Coupon::where('code', $request->coupon_code)
                    ->where('is_active', true)
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                              ->orWhere('expires_at', '>', now());
                    })
                    ->first();
                if ($coupon) {
                    $couponId = $coupon->id;
                    $discountAmount = ($subtotal * $coupon->discount_percent) / 100;
                }
            }

            $tipAmount = $request->tip_amount ?? 0;
            $totalAmount = $subtotal - $discountAmount + $tipAmount;

            // Update order totals
            $order->update([
                'coupon_id'       => $couponId,
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'total_amount'    => $totalAmount,
            ]);

            // Save status history
            $order->orderStatusHistories()->create([
                'old_status' => null,
                'new_status' => Order::STATUS_PLACED,
                'changed_by' => $user->id,
            ]);

            DB::commit();
            $order->load('orderItems');
            $order->load('orderStatusHistories');
            return $this->successResponse(new OrderResource($order), 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
