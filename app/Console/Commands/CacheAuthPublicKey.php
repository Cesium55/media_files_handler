<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CacheAuthPublicKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cache-auth-public-key';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get auth public key from auth service and puts it to cache';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $response = Http::get(
            config("auth.auth_service.protocol") . "://" .
            config("auth.auth_service.ip") . ":" .
            config("auth.auth_service.port") . "/" .
            config("auth.auth_service.endpoints.get_public_key")
        );


        if (!$response->successful()){
            $status = $response->status();
            Log::channel("custom")->error("Error while getting auth public key({$status})");
            return;
        }

        Cache::set("auth_public_key", $response->json()["key"]);
    }
}
