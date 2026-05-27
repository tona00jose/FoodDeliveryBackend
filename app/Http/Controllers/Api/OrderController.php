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
        // Default per page
        $per_page = $request->input('per_page', 10);

        // Build query
        // $query = Order::query();
        $query = Order::with('orderItems', 'orderStatusHistories');

        $user = auth()->user();
        if($user->role == 1) { // restaurant owner
            $query->whereHas('restaurant', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } else if($user->role == 2) { // customer
            $query->where('user_id', $user->id);
        }

        // Paginate
        if ($per_page == 0) {
            $collection = $query->get();
            $orders = new LengthAwarePaginator(
                $collection,
                $collection->count(),
                $collection->count(), // all in one page
                0,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );
        } else {
            $orders = $query->paginate($per_page);
        }

        // Return paginated resource
        return $this->successResponse([
            'orders' => OrderResource::collection($orders),
            'links' => OrderResource::collection($orders)->response()->getData()->links,
            'meta' => OrderResource::collection($orders)->response()->getData()->meta,
        ]);
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
        $order = Order::find($id);        
        if($order){
            $order->load('orderItems');
            $order->load('orderStatusHistories');
            $user = auth()->user();
            if($user->role == 0) { // admin
                return $this->successResponse(new OrderResource($order));
            } else if($user->role == 2) { // customer
                if($order->user_id != $user->id) { 
                    return $this->errorResponse(__('message.cannot_access'), 403);
                } else {
                    return $this->successResponse(new OrderResource($order));
                }
            } else {    // restaurant owner
                if($order->restaurant->user_id == $user->id) {
                    return $this->successResponse(new OrderResource($order));
                } else {
                    return $this->errorResponse(__('message.can_access_only_restaurant_owner'), 403);
                }
            }
        } else {
            return $this->errorResponse(__('message.not_found_msg'), 404);
        }
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
        $order = Order::find($id);
        if($order) {
            $order->orderItems()->delete();
            $order->orderStatusHistories()->delete();
            $order->delete();
            return $this->successResponse(new OrderResource($order), 200);
        } else {
            return $this->errorResponse(__('message.not_found_msg'), 404);
        }
    }

    /**
     * Update status in storage.
     */
    public function updateStatus(Request $request, string $id)
    {
        $order = Order::find($id);
        if($order) {
            $user = auth()->user();
            $allowed_access = false;
            if($user->role == 0) { // admin
                $allowed_access = true;
            } else if($user->role == 2) { // customer
                if($order->user_id == $user->id) { 
                    $allowed_access = true;
                }
            } else {    // restaurant owner
                if($order->restaurant->user_id == $user->id) {
                    $allowed_access = true;
                }
            }
            
            if($allowed_access) {
                $validator = Validator::make($request->all(), [
                    'status' => 'required|integer|in:0,1,2,3,4,5,6',                    
                ]);

                if ($validator->fails()) {
                    return $this->errorResponse($validator->messages(), 422);
                }

                $old_status = $order->status;
                $new_status = $request->status;
                $is_valid_status = false;
                if($old_status != $new_status) {
                    if($user->role == 0) {             // admin
                        $is_valid_status = true;
                    } else if($user->role == 2) {      // customer
                        if($old_status != Order::STATUS_REJECTED) {    
                            if($old_status == Order::STATUS_PLACED) {
                                if($new_status == Order::STATUS_CANCELLED || $new_status == Order::STATUS_RECEIVED) {
                                    $is_valid_status = true;
                                }
                            } else if($old_status == Order::STATUS_DELIVERED && $new_status == Order::STATUS_RECEIVED) {
                                $is_valid_status = true;
                            }   
                        }
                    } else {    // restaurant owner
                        if($old_status != Order::STATUS_CANCELLED && $old_status != Order::STATUS_RECEIVED) {
                            if($new_status == Order::STATUS_PLACED || $new_status == Order::STATUS_PROCESSING 
                            || $new_status == Order::STATUS_IN_ROUTE || $new_status == Order::STATUS_DELIVERED
                            || $new_status == Order::STATUS_REJECTED) {
                                $is_valid_status = true;
                            }
                        } 
                    }
                }

                if($is_valid_status) {
                    $order->update([ 'status' => $new_status ]);
                    $order->orderStatusHistories()->create([
                        'old_status' => $old_status,
                        'new_status' => $new_status,
                        'changed_by' => $user->id,
                    ]);
                    return $this->successResponse(new OrderResource($order), 200, __("message.success"));
                } else {
                    return $this->errorResponse(__('message.cannot_change_status'), 403);
                }
            } else {
                return $this->errorResponse(__('message.can_access_only_admin_or_restaurant_owner'), 403);
            }
        } else {
            return $this->errorResponse(__('message.not_found_msg'), 404);
        }
    }
}
