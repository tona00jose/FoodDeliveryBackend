<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\RestaurantResource;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

class RestaurantController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        // Default per page
        $per_page = $request->input('per_page', 10);

        // Build query
        // $query = Restaurant::with('meals');
        $query = Restaurant::query();

        if($user->role == self::ROLE_RESTAURANT_OWNER) { // restaurant owner
            $query->where('user_id', $user->id);
        } else if($user->role == self::ROLE_CUSTOMER) { // customer
            $query->where('is_blocked', 0);
            $query->whereHas('user', function ($userQuery) {
                $userQuery->where('is_blocked', 0);
            });
        }

        // Optional search
        if ($request->has('search_string') && $request->search_string != '') {
            $search = $request->search_string;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('user', function ($fq) use ($search) {
                    $fq->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Paginate
        if ($per_page == 0) {
            $collection = $query->get();
            $restaurants = new LengthAwarePaginator(
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
            $restaurants = $query->paginate($per_page);
        }

        // Return paginated resource
        return $this->successResponse([
            'restaurants' => RestaurantResource::collection($restaurants),
            'links' => RestaurantResource::collection($restaurants)->response()->getData()->links,
            'meta' => RestaurantResource::collection($restaurants)->response()->getData()->meta,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:255',
            'user_id' => [
                auth()->user()->role == self::ROLE_ADMIN ? 'required' : 'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    // user_id must belong to role 1 user
                    if ($value) {
                        if(auth()->user()->role == self::ROLE_ADMIN) { // admin
                            $user = User::find($value);
                            if (!$user || $user->role != self::ROLE_RESTAURANT_OWNER) {
                                $fail(__('message.selected_user_must_have_restaurant_owner_role'));
                            }
                        }
                    }
                },
            ],
            'description' => 'nullable|string',
            'is_blocked' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }

        $user_id = auth()->user()->id;
        if(auth()->user()->role == self::ROLE_ADMIN) { // admin
            $user_id = $request->user_id;
        }

        $restaurant = new Restaurant();
        $restaurant->name = $request->name;
        $restaurant->user_id = $user_id;
        if($request->has('description') && $request->description != "") $restaurant->description = $request->description;
        if($request->has('is_blocked') && $request->is_blocked != "") $restaurant->is_blocked = $request->is_blocked; else $restaurant->is_blocked = 0;
        $restaurant->save();

        return $this->successResponse(new RestaurantResource($restaurant), 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // $restaurant = Restaurant::with('meals')->find($id);
        $restaurant = Restaurant::find($id);
        if($restaurant){
            $user = auth()->user();
            if($user->role == self::ROLE_ADMIN) { // admin
                return $this->successResponse(new RestaurantResource($restaurant));
            } else if($user->role == self::ROLE_CUSTOMER) { // customer
                if($restaurant->is_blocked == 1) { // blocked restaurant
                    return $this->errorResponse(__('message.data_is_blocked'), 403);
                } else if($restaurant->user->is_blocked == 1) { // blocked restaurant owner
                    return $this->errorResponse(__('message.restaurant_owner_is_blocked'), 403);
                } else {
                    return $this->successResponse(new RestaurantResource($restaurant));
                }
            } else {    // restaurant owner
                if($restaurant->user_id == $user->id) {
                    return $this->successResponse(new RestaurantResource($restaurant));
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
        $restaurant = Restaurant::find($id);
        if($restaurant) {
            $allowed_access = false;
            if(auth()->user()->role == self::ROLE_ADMIN) { // admin
                $allowed_access = true;
            } else {    // restaurant owner
                if($restaurant->user_id == auth()->user()->id) {
                    $allowed_access = true;
                }
            }
            if($allowed_access) {
                $validator = Validator::make($request->all(), [
                    'name' => 'nullable|string|max:255',
                    'user_id' => [
                        'nullable',
                        'integer',
                        'exists:users,id',
                        function ($attribute, $value, $fail) {
                            // user_id must belong to role 1 user
                            if ($value) {
                                if(auth()->user()->role == self::ROLE_ADMIN) { // admin
                                    $user = User::find($value);
                                    if (!$user || $user->role != self::ROLE_RESTAURANT_OWNER) {
                                        $fail(__('message.selected_user_must_have_restaurant_owner_role'));
                                    }
                                }
                            }
                        },
                    ],
                    'description' => 'nullable|string',
                    'is_blocked' => 'nullable|integer|in:0,1',
                ]);

                if ($validator->fails()) {
                    return $this->errorResponse($validator->messages(), 422);
                }

                $user_id = auth()->user()->id;
                if(auth()->user()->role == self::ROLE_ADMIN) { // admin
                    $user_id = $request->user_id;
                }

                $restaurant->update([
                    'name' => $request->has('name')  && $request->name != "" ? $request->name : $restaurant->name,
                    'user_id' => $request->has('user_id')  && $request->user_id != "" ? $user_id : $restaurant->user_id,
                    'description' => $request->has('description') && $request->description != "" ? $request->description : $restaurant->description,
                    'is_blocked' => $request->has('is_blocked') && $request->is_blocked != "" ? $request->is_blocked : $restaurant->is_blocked,
                ]);
                return $this->successResponse(new RestaurantResource($restaurant), 200, __("message.success"));
            } else {
                return $this->errorResponse(__('message.can_access_only_admin_or_restaurant_owner'), 403);
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
        $restaurant = Restaurant::find($id);
        if($restaurant) {
            $allowed_access = false;
            if(auth()->user()->role == self::ROLE_ADMIN) { // admin
                $allowed_access = true;
            } else {    // restaurant owner
                if($restaurant->user_id == auth()->user()->id) {
                    $allowed_access = true;
                }
            }
            if($allowed_access) {
                $restaurant->delete();
                return $this->successResponse(new RestaurantResource($restaurant), 200);
            } else {
                return $this->errorResponse(__('message.can_access_only_admin_or_restaurant_owner'), 403);
            }
        } else {
            return $this->errorResponse(__('message.not_found_msg'), 404);
        }
    }

    /**
     * Block the specified resource from storage.
     */
    public function blockItem(string $id)
    {
        $restaurant = Restaurant::find($id);
        if($restaurant) {
            $allowed_access = false;
            if(auth()->user()->role == self::ROLE_ADMIN) { // admin
                $allowed_access = true;
            } else {    // restaurant owner
                if($restaurant->user_id == auth()->user()->id) {
                    $allowed_access = true;
                }
            }
            if($allowed_access) {
                $restaurant->update([
                    'is_blocked' => 1,
                ]);
                return $this->successResponse(new RestaurantResource($restaurant), 200);
            } else {
                return $this->errorResponse(__('message.can_access_only_admin_or_restaurant_owner'), 403);
            }
        } else {
            return $this->errorResponse(__('message.not_found_msg'), 404);
        }
    }
}
