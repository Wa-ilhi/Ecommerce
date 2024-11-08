<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Log;

class BuyerMiddleware
{
    protected $jwtSecret = 'aVVV1A05LlaIgk-CsZnBj1sCEgUwzGjPG4lbaXP20A4'; // This JWTSecret is from the User's Group

    public function handle(Request $request, Closure $next)
    {
        // Get the token and decode it
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $token = str_replace('Bearer ', '', $authHeader);
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            $roleId = $decoded->data->role_id ?? null;

            // If it's not a buyer (role_id !== 2), deny access
            if ((int)$roleId !== 2) {
                return response()->json(['error' => 'Permission Denied'], 403);
            }

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }
    }
}
