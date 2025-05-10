<?php

namespace App\Services;

use App\Models\Clip;
use Illuminate\Support\Facades\Cache;

class ClipsService
{
    public function getVideoClips(int $video_id)
    {
        $clips = Cache::get('video_clips'.$video_id);

        if ($clips != null) {
            return $clips;
        }

        $clips = Clip::where('video_id', $video_id)->get();

        Cache::set('video_clips'.$video_id, $clips, 600);

        return $clips;

    }

    public function findByNumber(int $video_id, int $clip_number)
    {

        $clip = Clip::where('title', "clip_{$video_id}_{$clip_number}")->first();

        return $clip;

    }

    public function getClipByTiming(int $video_id, float $timing)
    {
        $videoService = new VideoService;
        $video = $videoService->get($video_id);

        $clip_number = $this->timingBinarySearch($video->clip_intervals, $timing);

        if ($clip_number == -1) {
            abort(404, 'Not found (wrong timing)');
        }

        $clip = $this->findByNumber($video_id, $clip_number);

        if (! $clip) {
            abort(404, 'Not found (clip not found)');
        }

        return $clip;

    }

    public function timingBinarySearch(array $timings, float $timing)
    {
        $left = 0;
        $right = count($timings) - 1;
        $index = -1;

        while ($left <= $right) {
            $mid = intdiv($left + $right, 2);
            [$start, $end] = $timings[$mid];

            if ($start <= $timing && $timing <= $end) {
                $index = $mid;
                break;
            } elseif ($timing < $start) {
                $right = $mid - 1;
            } else {
                $left = $mid + 1;
            }
        }

        if ($index == -1) {
            return $index;
        }

        if (
            $index != count($timings) - 1 &&
            $timings[$index + 1][0] <= $timing && $timing <= $timings[$index + 1][1] &&
            min(abs($timings[$index + 1][0] - $timing), abs($timings[$index + 1][1] - $timing)) >
            min(abs($timings[$index][0] - $timing), abs($timings[$index][1] - $timing))
        ) {
            return $index + 1;
        }

        if (
            $index != 0 &&
            $timings[$index - 1][0] <= $timing && $timing <= $timings[$index - 1][1] &&
            min(abs($timings[$index - 1][0] - $timing), abs($timings[$index - 1][1] - $timing)) >
            min(abs($timings[$index][0] - $timing), abs($timings[$index][1] - $timing))
        ) {
            return $index - 1;
        }

        return $index;
    }

    public function getClipsPaginated(int $video_id)
    {

        $page = request()->get('page', 1);

        $clips = Cache::get("clips_paginated_{$video_id}_{$page}");

        if ($clips) {
            return $clips;
        }

        $clips = Clip::where('video_id', $video_id)->paginate(10);

        if ($clips->isEmpty()) {
            return $clips;
        }

        Cache::set("clips_paginated_{$video_id}_{$page}", $clips, config('cache.defaul_cache_ttl'));

        return $clips;
    }
}
