<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;


class AuthMiddleware
{



    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $auth_role): Response
    {


        if(!in_array($auth_role, ["admin", "authorized"])){
            return response()->json(["message" => "Server error: Unkown role"], 500);
        }

        $token = $request->header("Authorization");

        if(!$token){
            return response()->json(["message" => "No access token provided"], 401);
        }

        $decoded_jwt = AuthMiddleware::verifyToken($token);

        if(!$decoded_jwt){
            return response()->json(["message" => "Unauthorized"], 401);
        }
        $type = $decoded_jwt->type;
        $id = $decoded_jwt->id;
        $is_admin = (bool) $decoded_jwt->is_admin;
        Log::channel("custom_log")->info("Client {$type} with id=$id (admin=$is_admin) requesting to all videos ");


        if($auth_role == "admin" and !$is_admin){
            return response()->json(["message" => "Forbidden"], 403);
        }

        return $next($request);
    }



    public static function verifyToken($token)
    {
        try {
            $publicKey = Cache::get("auth_public_key");

            if (!$publicKey) {
                throw new Exception("Public key not found!");
            }

            return JWT::decode($token, new Key($publicKey, 'RS256'), );
        } catch (Exception $e) {
            return null;
        }
    }
}
