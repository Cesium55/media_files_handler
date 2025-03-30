<?php

namespace App\Services;

use App\Models\Clip;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ClipsService
{



    public function getVideoClips(int $video_id)
    {
        $clips = Cache::get("video_clips" . $video_id);

        if ($clips != null) {
            return $clips;
        }

        $clips = Clip::where("video_id", $video_id)->get();

        Cache::set("video_clips" . $video_id, $clips, 600);

        return $clips;

    }


    public function findByNumber(int $video_id, int $clip_number){


        $clip = Clip::where("title", "clip_{$video_id}_{$clip_number}")->first();

        return $clip;

    }

    public function getClipByTiming(int $video_id, float $timing)
    {
        $videoService = new VideoService();
        $video = $videoService->get($video_id);

        $clip_number = $this->timingBinarySearch($video->clip_intervals, $timing);

        if ($clip_number == -1){
            abort(404, "Not found (wrong timing)");
        }

        $clip = $this->findByNumber($video_id, $clip_number);

        if (!$clip){
            abort(404, "Not found (clip not found)");
        }

        return $clip;

    }


    public function timingBinarySearch(array $segments, float $target)
    {


        // Log::info($target);
        // Log::info($segments[0]);

        $bestIndex = -1;
        $maxDepth = 0;

        foreach ($segments as $index => $segment) {
            list($start, $end) = $segment;

            if ($target >= $start && $target <= $end) {
                $depth = min($target - $start, $end - $target);

                if ($bestIndex === -1 || $depth > $maxDepth) {
                    $bestIndex = $index;
                    $maxDepth = $depth;
                }
            }
        }

        return $bestIndex;
    }
}
