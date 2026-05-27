<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserRoleCheckMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role_name): Response
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        if($role_name == 'admin') {
            if (auth()->user()->role != 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only administrator can access.',
                    "data" => null
                ], 403);
            } else {
                return $next($request);
            }
        } else if($role_name == 'admin_or_restaurant_owner') {
            if (auth()->user()->role > 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only Administrator or Restaurant Owner can access.',
                    "data" => null
                ], 403);
            } else {
                return $next($request);
            }
        }
        return response()->json([
            'status' => 'error',
            'message' => 'The user do not have access.',
            "data" => null
        ], 403);
    }
}
