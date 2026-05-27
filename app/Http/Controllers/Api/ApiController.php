<?php

namespace App\Http\Controllers\Api;

use App\Traits\ApiResponser;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
    use ApiResponser;

    // Roles
    public const ROLE_ADMIN = 0;
    public const ROLE_RESTAURANT_OWNER = 1;
    public const ROLE_CUSTOMER = 2;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            return $next($request);
        });
    }    
}
