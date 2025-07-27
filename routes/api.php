<?php

use App\Http\Controllers\ClipsController;
use App\Http\Controllers\VideoController;
use App\Http\Middleware\AuthMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('videos')->group(function () {
        if (config('app.debug')) {
            Route::get('all/', [VideoController::class, 'getAllPaginated'])
                ->middleware(AuthMiddleware::class.':admin');
            Route::post('recut/', [VideoController::class, 'recut'])->middleware(AuthMiddleware::class.':admin');

            Route::get('/swagger/swagger.json', function () {
                $path = public_path('swagger/swagger.json');

                if (! file_exists($path)) {
                    abort(404);
                }

                return Response::file($path, [
                    'Access-Control-Allow-Origin' => '*',
                ]);
            });
        }

        Route::get('/', [VideoController::class, 'getAllPaginated']);

        Route::get('/{video_id}/clips', [ClipsController::class, 'get_clips']);
        Route::get('/{video_id}/intervals', [ClipsController::class, 'get_intervals']);

        Route::post('/video', [VideoController::class, 'create'])->middleware(AuthMiddleware::class.':admin');
        Route::post('/{video_id}/upload-subs', [VideoController::class, 'load_subs'])->middleware(AuthMiddleware::class.':admin');
        Route::post('/{video_id}/upload-video', [VideoController::class, 'load_video'])->middleware(AuthMiddleware::class.':admin');
        Route::delete('/{video_id}', [VideoController::class, 'delete'])->middleware(AuthMiddleware::class.':admin');
        Route::get('/{video_id}', [VideoController::class, 'get_video']);
        Route::get('/{video_id}/logs', [VideoController::class, 'get_video_logs'])->middleware(AuthMiddleware::class.':admin');

        Route::get('/{video_id}/clip-by-timing', [ClipsController::class, 'getClipByTiming']);

        Route::post('/{video_id}/recut-video', [VideoController::class, 'recut'])->middleware(AuthMiddleware::class.':admin');

    });

    Route::prefix('clips')->group(function () {
        Route::get('/{clip_id}', [ClipsController::class, 'getClip']);
    });
});
