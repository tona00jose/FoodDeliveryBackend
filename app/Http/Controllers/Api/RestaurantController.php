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
        $query = Restaurant::query();
        if($user->role == 1) { // restaurant owner
            $query->where('user_id', $user->id);
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
            'name'              => 'required|string',
            'user_id' => [
                auth()->user()->role == 0 ? 'required' : 'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    // user_id must belong to role 1 user
                    if ($value) {
                        $user = User::find($value);
                        if (!$user || $user->role != 1) {
                            $fail('The selected user must have the Restaurant Owner role.');
                        }
                    }
                },
            ],
            'description'       => 'nullable|string',
            'is_blocked'        => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }

        $user_id = auth()->user()->id;
        if(auth()->user()->role == 0) { // admin
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
