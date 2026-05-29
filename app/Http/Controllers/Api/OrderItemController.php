<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use App\Http\Resources\OrderItemResource;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\User;
use App\Models\Meal;
use App\Models\Coupon;

use App\Http\Resources\OrderResource;

use Illuminate\Validation\Rule;

class OrderItemController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Default per page
        $per_page = (int) $request->input('per_page', 10);
        if($per_page < 0) $per_page = 0;

        // Build query
        $query = OrderItem::query();

        // Paginate
        if ($per_page == 0) {
            $collection = $query->get();
            $order_items = new LengthAwarePaginator(
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
            $order_items = $query->paginate($per_page);
        }

        // Return paginated resource
        return $this->successResponse([
            'order_items' => OrderItemResource::collection($order_items),
            'links' => OrderItemResource::collection($order_items)->response()->getData()->links,
            'meta' => OrderItemResource::collection($order_items)->response()->getData()->meta,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // validation check ===================================================================
        $is_valid = false;
        $rules = [
            'order_id'      => 'required|integer|exists:orders,id,deleted_at,NULL',
            'quantity'      => 'required|integer|min:1'
        ];
        // 'meal_id'       => 'required|integer|exists:meals,id,deleted_at,NULL'
        if($request->order_id) {
            $order = Order::find($request->order_id);
            if($order) {
                $mealRule = Rule::exists('meals', 'id')
                    ->where(function ($query) use ($order) {
                        $query->whereNull('meals.deleted_at')
                            ->where('meals.restaurant_id', $order->restaurant_id);
                    });

                $rules['meal_id'] = ['required', 'integer', $mealRule];
                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    return $this->errorResponse($validator->messages(), 422);
                }
                DB::beginTransaction();
                try {
                    $subtotal = 0;
                    $meal = Meal::findOrFail($request->meal_id);
                    $subtotal = $meal->price * $request->quantity;

                    // create order item --------------------------------------------------
                    $order_item = new OrderItem();
                    $order_item->order_id = $request->order_id;
                    $order_item->meal_id = $meal->id;
                    $order_item->quantity = $request->quantity;
                    $order_item->price_at_order_time = $meal->price;
                    $order_item->subtotal = $subtotal;
                    $order_item->save();
                    
                    // update order --------------------------------------------------------
                    $order_subtotal = 0;
                    foreach ($order->orderItems as $item) {
                        $order_subtotal += $item->price_at_order_time * $item->quantity;
                    }
                    // Coupon check
                    $discountAmount = 0;
                    if($order->coupon_id) {
                        $coupon = Coupon::find($order->coupon_id);
                        if ($coupon) {
                            $discountAmount = ($order_subtotal * $coupon->discount_percent) / 100;
                        }
                    }

                    $tipAmount = $order->tip_amount ?? 0;
                    $totalAmount = $order_subtotal - $discountAmount + $tipAmount;

                    // Update order totals
                    $order->update([
                        'subtotal'        => $order_subtotal,
                        'discount_amount' => $discountAmount,
                        'total_amount'    => $totalAmount,
                    ]);
                        
                    DB::commit();

                    return $this->successResponse(new OrderItemResource($order_item), 200);

                } catch (\Exception $e) {
                    DB::rollBack();
                    return $this->errorResponse($e->getMessage(), 500);
                }
            }             
        } 
        if(!$is_valid) {
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->errorResponse($validator->messages(), 422);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $order_item = OrderItem::find($id);        
        if($order_item){
            return $this->successResponse(new OrderItemResource($order_item));
        } else {
            return $this->errorResponse(__('message.not_found_msg'), 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $order_item = OrderItem::find($id);        
        if($order_item){
            $is_valid = false;
            $rules = [
                'quantity'      => 'nullable|integer|min:1'
            ];
            // 'meal_id'       => 'required|integer|exists:meals,id,deleted_at,NULL'

            $order_id = $order_item->order_id;
            // if($request->has('order_id')  && $request->order_id != "") {
            //     $order_id = $request->order_id;
            // }
            if($order_id) {
                $order = Order::find($order_id);
                if($order) {
                    $mealRule = Rule::exists('meals', 'id')
                        ->where(function ($query) use ($order) {
                            $query->whereNull('meals.deleted_at')
                                ->where('meals.restaurant_id', $order->restaurant_id);
                        });

                    $rules['meal_id'] = ['nullable', 'integer', $mealRule];
                    $validator = Validator::make($request->all(), $rules);

                    if ($validator->fails()) {
                        return $this->errorResponse($validator->messages(), 422);
                    }

                    $meal_id = $order_item->meal_id;
                    if($request->has('meal_id')  && $request->meal_id != "") {
                        $meal_id = $request->meal_id;
                    }

                    $quantity = $order_item->quantity;
                    if($request->has('quantity')  && $request->quantity != "") {
                        $quantity = $request->quantity;
                    }

                    DB::beginTransaction();
                    try {
                        $subtotal = 0;
                        $meal = Meal::findOrFail($meal_id);
                        $subtotal = $meal->price * $quantity;

                        // update order item --------------------------------------------------

                        $order_item->update([
                            'order_id' => $order_id,
                            'meal_id' => $meal_id,
                            'quantity' => $quantity,
                            'price_at_order_time' => $meal->price,
                            'subtotal' => $subtotal,
                        ]);
                        
                        // update order --------------------------------------------------------
                        $order_subtotal = 0;
                        foreach ($order->orderItems as $item) {
                            $order_subtotal += $item->price_at_order_time * $item->quantity;
                        }

                        // Coupon check
                        $discountAmount = 0;
                        if($order->coupon_id) {
                            $coupon = Coupon::find($order->coupon_id);
                            if ($coupon) {
                                $discountAmount = ($order_subtotal * $coupon->discount_percent) / 100;
                            }
                        }

                        $tipAmount = $order->tip_amount ?? 0;
                        $totalAmount = $order_subtotal - $discountAmount + $tipAmount;

                        // Update order totals
                        $order->update([
                            'subtotal'        => $order_subtotal,
                            'discount_amount' => $discountAmount,
                            'total_amount'    => $totalAmount,
                        ]);
                            
                        DB::commit();

                        return $this->successResponse(new OrderItemResource($order_item), 200);

                    } catch (\Exception $e) {
                        DB::rollBack();
                        return $this->errorResponse($e->getMessage(), 500);
                    }
                }             
            } 
            if(!$is_valid) {
                $validator = Validator::make($request->all(), $rules);
                if ($validator->fails()) {
                    return $this->errorResponse($validator->messages(), 422);
                }
            }
        } else {
            return $this->errorResponse(__('message.not_found_msg'), 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order_item = OrderItem::find($id);
        if($order_item) {
            DB::beginTransaction();
            try {
                // delete order item
                $order_item->delete();
                // update order
                $order = Order::find($order_item->order_id);
                if($order) {
                    $order_subtotal = 0;
                    foreach ($order->orderItems as $item) {
                        $order_subtotal += $item->price_at_order_time * $item->quantity;
                    }

                    // Coupon check
                    $discountAmount = 0;
                    if($order->coupon_id) {
                        $coupon = Coupon::find($order->coupon_id);
                        if ($coupon) {
                            $discountAmount = ($order_subtotal * $coupon->discount_percent) / 100;
                        }
                    }

                    $tipAmount = $order->tip_amount ?? 0;
                    $totalAmount = $order_subtotal - $discountAmount + $tipAmount;

                    // Update order totals
                    $order->update([
                        'subtotal'        => $order_subtotal,
                        'discount_amount' => $discountAmount,
                        'total_amount'    => $totalAmount,
                    ]);
                }
                DB::commit();
                return $this->successResponse(new OrderItemResource($order_item), 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->errorResponse($e->getMessage(), 500);
            }
        } else {
            return $this->errorResponse(__('message.not_found_msg'), 404);
        }
    }
}
