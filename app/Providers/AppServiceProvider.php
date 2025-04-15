<?php

namespace App\Providers;

use App\Events\VideoCreated;
use App\Listeners\SendNewVideoNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Event::listen(
        //     VideoCreated::class,
        //     SendNewVideoNotification::class
        // );
    }
}
