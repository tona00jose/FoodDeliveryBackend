<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

class UserController extends ApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Default per page
        $per_page = (int) $request->input('per_page', 10);
        if($per_page < 0) $per_page = 0;

        // Build query
        // $query = User::with('restaurants');
        $query = User::query();

        // Optional search
        if ($request->has('search_string') && $request->search_string != '') {
            $search = $request->search_string;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }

        // Paginate
        if ($per_page == 0) {
            $collection = $query->get();

            $users = new LengthAwarePaginator(
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
            $users = $query->paginate($per_page);
        }

        // Return paginated resource
        return $this->successResponse([
            'users' => UserResource::collection($users),
            'links' => UserResource::collection($users)->response()->getData()->links,
            'meta' => UserResource::collection($users)->response()->getData()->meta,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|max:255|unique:users,email,NULL,id,deleted_at,NULL', // unique:table,column,except,idColumn,whereColumn,whereValue
            'password'          => 'required|string|max:255',
            'role'              => 'nullable|integer|in:0,1,2',
            'is_blocked'        => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }

        // Create user
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = $request->password;
        if($request->has('role') && $request->role != "") $user->role = $request->role; else $user->role = self::ROLE_CUSTOMER;
        $user->is_super_admin = 0;
        if($request->has('is_blocked') && $request->is_blocked != "") $user->is_blocked = $request->is_blocked; else $user->is_blocked = 0;
        $user->save();

        return $this->successResponse(new UserResource($user), 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // $user = User::with('restaurants')->find($id);
        $user = User::find($id);
        if($user)
            return $this->successResponse(new UserResource($user));
        else
            return $this->errorResponse(__('message.not_found_msg'), 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);
        if($user) {
            $validator = Validator::make($request->all(), [
                'name'  => 'nullable|string|max:255',
                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                    'unique:users,email,'.$user->id.',id,deleted_at,NULL',
                    function ($attribute, $value, $fail) use ($user) {
                        // Super Admin cannot be changed role
                        if ($user->is_super_admin == 1 && $user->id != auth()->user()->id) {
                            $fail(__('message.super_administrator_can_change_email'));
                        }
                    },
                ],
                'password' => [
                    'nullable',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) use ($user) {
                        // Super Admin cannot be changed role
                        if ($user->is_super_admin == 1 && $user->id != auth()->user()->id) {
                            $fail(__('message.super_administrator_can_change_password'));
                        }
                    },
                ],
                'role' => [
                    'nullable',
                    'integer',
                    'in:0,1,2',
                    function ($attribute, $value, $fail) use ($user) {
                        // Super Admin cannot be changed role
                        if (($user->is_super_admin == 1)&& ($value > 0)) {
                            $fail(__('message.cannot_be_changed_role'));
                        }
                    },
                ],
                'is_blocked' => [
                    'nullable',
                    'integer',
                    'in:0,1',
                    function ($attribute, $value, $fail) use ($user) {
                        // Super Admin and logined self user cannot be blocked
                        if (($user->is_super_admin == 1 || $user->id == auth()->user()->id )&& $value == 1) {
                            $fail(__('message.cannot_be_blocked'));
                        }
                    },
                ],
            ]);
            if ($validator->fails()) {
                return $this->errorResponse($validator->messages(), 422);
            }
            $user->update([
                'name' => $request->has('name')  && $request->name != "" ? $request->name : $user->name,
                'email' => $request->has('email')  && $request->email != "" ? $request->email : $user->email,
                'password' => $request->has('password') && $request->password != "" ? Hash::make($request->password) : $user->password,
                'role' => $request->has('role') && $request->role != "" ? $request->role : $user->role,
                'is_blocked' => $request->has('is_blocked') && $request->is_blocked != "" ? $request->is_blocked : $user->is_blocked,
            ]);
            return $this->successResponse(new UserResource($user), 200, __("message.success"));
        } else {
            return $this->errorResponse(__('message.not_found_msg'), 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);
        if($user) {
            if($user->is_super_admin == 1 || $user->id == auth()->user()->id ) {
                return $this->errorResponse(__('message.cannot_be_deleted'), 422);
            } else {
                $user->delete();
                return $this->successResponse(new UserResource($user), 200);
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
        $user = User::find($id);
        if($user) {
            if($user->is_super_admin == 1 || $user->id == auth()->user()->id ) {
                return $this->errorResponse(__('message.cannot_be_blocked'), 422);
            } else {
                $user->update([
                    'is_blocked' => 1,
                ]);
                return $this->successResponse(new UserResource($user), 200);
            }
        } else {
            return $this->errorResponse(__('message.not_found_msg'), 404);
        }
    }
}
