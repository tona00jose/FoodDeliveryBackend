<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Meal;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\MealResource;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

use Illuminate\Validation\Rule;

class MealController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        // Default per page
        $per_page = (int) $request->input('per_page', 10);
        if($per_page < 0) $per_page = 0;

        // Build query
        $query = Meal::query();
        if($user->role == self::ROLE_RESTAURANT_OWNER) { // restaurant owner
            $query->whereHas('restaurant', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } 

        // Optional search
        if ($request->has('search_string') && $request->search_string != '') {
            $search = $request->search_string;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('restaurant', function ($fq) use ($search) {
                    $fq->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Paginate
        if ($per_page == 0) {
            $collection = $query->get();
            $meals = new LengthAwarePaginator(
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
            $meals = $query->paginate($per_page);
        }

        // Return paginated resource
        return $this->successResponse([
            'meals' => MealResource::collection($meals),
            'links' => MealResource::collection($meals)->response()->getData()->links,
            'meta' => MealResource::collection($meals)->response()->getData()->meta,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $rules = [
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'price'         => 'required|numeric|min:0|max:9999999999999.99|decimal:0,2',
            'is_blocked'    => 'nullable|integer|in:0,1',
        ];
        // 'restaurant_id'     => 'required|integer|exists:restaurants,id'
        $restaurantRule = Rule::exists('restaurants', 'id')->whereNull('deleted_at');
        if ($user->role == self::ROLE_RESTAURANT_OWNER) { // restaurant owner
            $restaurantRule = $restaurantRule->where('user_id', $user->id);
        } else if ($user->role == self::ROLE_ADMIN) { // admin
            $restaurantRule = $restaurantRule->where(function ($query) {
                $query->whereExists(function ($userQuery) {
                        $userQuery->select(DB::raw(1))
                            ->from('users')
                            ->whereColumn('users.id', 'restaurants.user_id')
                            ->where('users.role', self::ROLE_RESTAURANT_OWNER)
                            ->whereNull('users.deleted_at');
                    });
            });
        }
        $rules['restaurant_id'] = ['required', 'integer', $restaurantRule];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }

        $meal = new Meal();
        $meal->name = $request->name;
        $meal->restaurant_id = $request->restaurant_id;
        $meal->price = $request->price;
        if($request->has('description') && $request->description != "") $meal->description = $request->description;
        if($request->has('is_blocked') && $request->is_blocked != "") $meal->is_blocked = $request->is_blocked; else $meal->is_blocked = 0;
        $meal->save();

        return $this->successResponse(new MealResource($meal), 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $meal = Meal::find($id);
        if($meal){
            $user = auth()->user();
            if($user->role == self::ROLE_ADMIN) { // admin
                return $this->successResponse(new MealResource($meal));
            } else {    // restaurant owner
                if($meal->restaurant->user_id == $user->id) {
                    return $this->successResponse(new MealResource($meal));
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
        $meal = Meal::find($id);
        if($meal) {
            $user = auth()->user();
            $allowed_access = false;
            if($user->role == self::ROLE_ADMIN) { // admin
                $allowed_access = true;
            } else {    // restaurant owner
                if($meal->restaurant->user_id == $user->id) {
                    $allowed_access = true;
                }
            }
            if($allowed_access) {
                $rules = [
                    'name'          => 'nullable|string|max:255',
                    'description'   => 'nullable|string',
                    'price'         => 'nullable|numeric|min:0|max:9999999999999.99|decimal:0,2',
                    'is_blocked'    => 'nullable|integer|in:0,1',
                ];
                $restaurantRule = Rule::exists('restaurants', 'id')->whereNull('deleted_at');
                if ($user->role == self::ROLE_RESTAURANT_OWNER) { // restaurant owner
                    $restaurantRule = $restaurantRule->where('user_id', $user->id);
                } else if ($user->role == self::ROLE_ADMIN) { // admin
                    $restaurantRule = $restaurantRule->where(function ($query) {
                        $query->whereExists(function ($userQuery) {
                                $userQuery->select(DB::raw(1))
                                    ->from('users')
                                    ->whereColumn('users.id', 'restaurants.user_id')
                                    ->where('users.role', self::ROLE_RESTAURANT_OWNER)
                                    ->whereNull('users.deleted_at');
                            });
                    });
                }
                $rules['restaurant_id'] = ['nullable', 'integer', $restaurantRule];
                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    return $this->errorResponse($validator->messages(), 422);
                }

                $meal->update([
                    'name' => $request->has('name')  && $request->name != "" ? $request->name : $meal->name,
                    'restaurant_id' => $request->has('restaurant_id')  && $request->restaurant_id != "" ? $request->restaurant_id : $meal->restaurant_id,
                    'description' => $request->has('description') && $request->description != "" ? $request->description : $meal->description,
                    'price' => $request->has('price')  && $request->price != "" ? $request->price : $meal->price,
                    'is_blocked' => $request->has('is_blocked') && $request->is_blocked != "" ? $request->is_blocked : $meal->is_blocked,
                ]);
                return $this->successResponse(new MealResource($meal), 200, __("message.success"));
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
        $meal = Meal::find($id);
        if($meal) {
            $allowed_access = false;
            if(auth()->user()->role == self::ROLE_ADMIN) { // admin
                $allowed_access = true;
            } else {    // restaurant owner
                if($meal->restaurant->user_id == auth()->user()->id) {
                    $allowed_access = true;
                }
            }
            if($allowed_access) {
                $meal->delete();
                return $this->successResponse(new MealResource($meal), 200);
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
        $meal = Meal::find($id);
        if($meal) {
            $allowed_access = false;
            if(auth()->user()->role == self::ROLE_ADMIN) { // admin
                $allowed_access = true;
            } else {    // restaurant owner
                if($meal->restaurant->user_id == auth()->user()->id) {
                    $allowed_access = true;
                }
            }
            if($allowed_access) {
                $meal->update([
                    'is_blocked' => 1,
                ]);
                return $this->successResponse(new MealResource($meal), 200);
            } else {
                return $this->errorResponse(__('message.can_access_only_admin_or_restaurant_owner'), 403);
            }
        } else {
            return $this->errorResponse(__('message.not_found_msg'), 404);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function getList(Request $request)
    {
        // Default per page
        $per_page = (int) $request->input('per_page', 10);
        if($per_page < 0) $per_page = 0;

        // Build query
        $query = Meal::query();
        $query->where('is_blocked', 0);
        $query->whereHas('restaurant', function ($restaurantQuery) {
            $restaurantQuery->where('is_blocked', 0);
            $restaurantQuery->whereHas('user', function ($userQuery) {
                $userQuery->where('is_blocked', 0);
            });
        });        

        // Optional search
        if ($request->has('search_string') && $request->search_string != '') {
            $search = $request->search_string;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('restaurant', function ($fq) use ($search) {
                    $fq->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Paginate
        if ($per_page == 0) {
            $collection = $query->get();
            $meals = new LengthAwarePaginator(
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
            $meals = $query->paginate($per_page);
        }

        // Return paginated resource
        return $this->successResponse([
            'meals' => MealResource::collection($meals),
            'links' => MealResource::collection($meals)->response()->getData()->links,
            'meta' => MealResource::collection($meals)->response()->getData()->meta,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function getItem(string $id)
    {
        $meal = Meal::find($id);
        if($meal){
            if($meal->is_blocked == 1) {                                                // blocked meal
                return $this->errorResponse(__('message.data_is_blocked'), 403);
            } else if($meal->restaurant->is_blocked == 1) {                             // blocked restaurant
                return $this->errorResponse(__('message.restaurant_is_blocked'), 403);
            } else if($meal->restaurant->user->is_blocked == 1) {                       // blocked restaurant owner
                return $this->errorResponse(__('message.restaurant_owner_is_blocked'), 403);
            } else {
                return $this->successResponse(new MealResource($meal));
            }
        } else {
            return $this->errorResponse(__('message.not_found_msg'), 404);
        }
    }
}
