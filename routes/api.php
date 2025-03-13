<?php

use App\Http\Controllers\ClipsController;
use App\Http\Controllers\LogsController;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoController;


Route::prefix("v1")->group(function () {
    Route::prefix("videos")->group(function () {
        if (config("app.debug")) {
            Route::get("all/", [VideoController::class, "get_all"]);
            Route::post("recut/", [VideoController::class, "recut"]);


            Route::get('/swagger/swagger.json', function () {
                $path = public_path('swagger/swagger.json');

                if (!file_exists($path)) {
                    abort(404);
                }

                return Response::file($path, [
                    'Access-Control-Allow-Origin' => '*'
                ]);
            });
        }

        Route::post("/video", [VideoController::class, "create"]);
        Route::delete("/video", [VideoController::class, "delete"]);
        Route::post("/upload-subs", [VideoController::class, "load_subs"]);

        Route::get("/logs", [LogsController::class, "get_logs"]);

        Route::post("/upload-video", [VideoController::class, "load_video"]);

        Route::get("/clips", [ClipsController::class, "get_clips"]);





        Route::get("/{video_id}", [VideoController::class, "get_video"]);
        Route::get("/{video_id}/logs", [VideoController::class, "get_video_logs"]);
    });
});
