<?php

use App\Http\Controllers\ClipsController;
use App\Http\Controllers\LogsController;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoController;


Route::prefix("v1")->group(function () {
    Route::prefix("videos")->group(function () {
        if (config("app.debug")){
            Route::get("all/", [VideoController::class, "get_all"]);
        }

        Route::post("video/", [VideoController::class, "create"]);
        Route::post("upload-subs/", [VideoController::class, "load_subs"]);

        Route::get("/logs", [LogsController::class, "get_logs"]);

        Route::post("upload-video", [VideoController::class, "load_video"]);

        Route::get("clips/", [ClipsController::class, "get_clips"]);
    });
});
