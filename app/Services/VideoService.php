<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VideoService
{
    public function get(int $video_id)
    {

        $video = Cache::get("video_{$video_id}");

        if ($video) {
            return $video;
        }

        $video = Video::find($video_id);
        if (! $video) {
            abort(404, "Video [id=$video_id] not found");
        }

        Cache::set("video_$video_id", $video, config('cache.defaul_cache_ttl'));

        return $video;
    }

    public function getAllPaginated()
    {

        $page = request()->get('page', 1);

        $videos = Cache::get("videos_paginated_{$page}");

        if ($videos) {
            Log::info('Videos from cache');

            return $videos;
        }

        $videos = Video::paginate(10);

        if ($videos->isEmpty()) {
            Log::info('Page to big no caching');

            return $videos;
        }

        Log::info('Videos from db, setting to cache');

        Cache::set("videos_paginated_{$page}", $videos, config('cache.defaul_cache_ttl'));

        return $videos;
    }
}
