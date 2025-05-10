<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthService
{
    private string $cacheAccessTokenKey = 'auth_access_token';

    public function getAuthorizationToken(): ?string
    {
        $token = Cache::get($this->cacheAccessTokenKey);

        if (! $token) {
            return null;
        }

        return 'Bearer '.$token;
    }

    public function initAccessToken(): bool
    {

        $endpoint = config('auth.auth_service.protocol').'://'.
        config('auth.auth_service.ip').':'.
        config('auth.auth_service.port').'/'.
        config('auth.auth_service.endpoints.api_login');

        Log::info("Request to $endpoint");

        $name = config('auth.own_service_name');
        $secret = config('auth.own_service_secret');
        Log::info("data : $name $secret");

        $response = Http::withHeaders(['Accept' => 'application/json'])->post(
            $endpoint,

            [
                'name' => config('auth.own_service_name'),
                'client_secret' => config('auth.own_service_secret'),
            ]

        );

        if (! $response->successful()) {
            $code = $response->status();
            Log::warning("init auth token response code - $code");

            return false;
        }

        Cache::set($this->cacheAccessTokenKey, $response->json()['api_token']);

        return true;

    }

    public function checkOwnAuth(bool $retryIfFails = false)
    {
        $auth_token = $this->getAuthorizationToken();

        if (! $auth_token) {
            if ($retryIfFails && $this->initAccessToken()) {
                return $this->checkOwnAuth();
            }

            return false;
        }

        $response = Http::withHeader('Authorization', $auth_token)->post(
            config('auth.auth_service.protocol').'://'.
            config('auth.auth_service.ip').':'.
            config('auth.auth_service.port').'/'.
            config('auth.auth_service.endpoints.api_auth')
        );

        if (! $response->successful() && $retryIfFails && $this->initAccessToken()) {
            return $this->checkOwnAuth();
        }

        return $response->successful();
    }
}
