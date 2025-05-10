<?php

namespace App\Listeners;

use App\Events\VideoCreated;
use App\Services\AuthService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendNewVideoNotification
{
    public array $endpoints;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        $this->endpoints = config('notifications.new_video_event_listener_endpoints');
    }

    /**
     * Handle the event.
     */
    public function handle(VideoCreated $event): void
    {
        $auth_service = new AuthService;

        if ($auth_service->checkOwnAuth(true)) {
            $auth_token = $auth_service->getAuthorizationToken();

            foreach ($this->endpoints as $endpoint) {
                $response = Http::withHeader('Authorization', $auth_token)
                    ->post($endpoint, ['video_id' => $event->video_id]);

                $code = $response->status();

                Log::info("Notification to $endpoint --- $code");
            }

        } else {
            Log::error('SendNewVideoNotification failed because of own auth fail');
        }
    }
}
