<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Validator;

class AuthController extends ApiController
{
    public function login(Request $request) {
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'password' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse(['message' => __('auth.invalid_credentials')], 401);
        }

        $user = Auth::user();

        if($user->is_blocked == 0) {
            $token = $user->createToken(
                'food-delivery-app',
                ['*'],
                now()->addDays(1)   //expiresAt
            )->plainTextToken;
            return $this->successResponse([
                    'user' => new UserResource($user),
                    'token' => $token
                ], 200);
        } else {
            auth()->user()->tokens()->delete();
            return $this->successResponse([], 200, __('auth.blocked'));
        }
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email,NULL,id,deleted_at,NULL',
            'password'  => 'required|string|max:255',
            'role'      => 'integer|in:1,2',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }

        $role = self::ROLE_CUSTOMER;
        if($request->role == self::ROLE_RESTAURANT_OWNER) $role = self::ROLE_RESTAURANT_OWNER;

        // DB::beginTransaction();
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $role,
            'is_super_admin' => 0,
            'is_blocked' => 0,
            'password' => Hash::make($request->password)
        ]);
        // DB::commit();
        
        return $this->successResponse(new UserResource($user), 200);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        return $this->successResponse([], 200, __('auth.logged_out'));
    }
}
