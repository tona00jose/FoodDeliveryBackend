<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Coupon;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\CouponResource;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

use Illuminate\Support\Str;

class CouponController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Default per page
        $per_page = $request->input('per_page', 10);

        // Build query
        $query = Coupon::query();

        // Optional search
        if ($request->has('search_string') && $request->search_string != '') {
            $search = $request->search_string;
            $query->where('code', 'like', "%{$search}%");
        }

        // Paginate
        if ($per_page == 0) {
            $collection = $query->get();

            $coupons = new LengthAwarePaginator(
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
            $coupons = $query->paginate($per_page);
        }

        // Return paginated resource
        return $this->successResponse([
            'coupons' => CouponResource::collection($coupons),
            'links' => CouponResource::collection($coupons)->response()->getData()->links,
            'meta' => CouponResource::collection($coupons)->response()->getData()->meta,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'discount_percent' => 'required|numeric|min:1|max:100',
            'expires_at'       => 'nullable|date',
            'is_active'        => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }

        // Generate unique coupon code
        do {
            $couponCode = strtoupper(Str::random(8));
        } while (Coupon::where('code', $couponCode)->exists());

        // Create user
        $coupon = new Coupon();
        $coupon->code = $couponCode;
        $coupon->discount_percent = $request->discount_percent;
        if($request->has('expires_at') && $request->expires_at != "") $coupon->expires_at = $request->expires_at;
        if($request->has('is_active') && $request->is_active != "") $coupon->is_active = $request->is_active; else $coupon->is_active = 1;
        $coupon->save();

        return $this->successResponse(new CouponResource($coupon), 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $coupon = Coupon::find($id);
        if($coupon)
            return $this->successResponse(new CouponResource($coupon));
        else
            return $this->errorResponse(__('message.not_found_msg'), 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $coupon = Coupon::find($id);
        if($coupon) {
            $validator = Validator::make($request->all(), [
                'discount_percent' => 'nullable|numeric|min:1|max:100',
                'expires_at'       => 'nullable|date',
                'is_active'        => 'nullable|integer|in:0,1',
                
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->messages(), 422);
            }

            $coupon->update([
                'discount_percent' => $request->has('discount_percent')  && $request->discount_percent != "" ? $request->discount_percent : $coupon->discount_percent,
                'expires_at' => $request->has('expires_at')  && $request->expires_at != "" ? $request->expires_at : $coupon->expires_at,
                'is_active' => $request->has('is_active') && $request->is_active != "" ? $request->is_active : $coupon->is_active,
            ]);
            return $this->successResponse(new CouponResource($coupon), 200, __("message.success"));
        } else {
            return $this->errorResponse(__('message.not_found_msg'), 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $coupon = Coupon::find($id);
        if($coupon) {
            $coupon->delete();
            return $this->successResponse(new CouponResource($coupon), 200);
        } else {
            return $this->errorResponse(__('message.not_found_msg'), 404);
        }
    }
}
