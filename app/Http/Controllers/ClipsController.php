<?php

namespace App\Http\Controllers;

use App\Models\Clip;
use App\Services\ClipsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ClipsController extends Controller
{
    // function get_clips(int $video_id){
    //     return Clip::where("video_id", $video_id)->get();
    // }


    function get_clips(int $video_id, ClipsService $clipsService){
        return $clipsService->getClipsPaginated($video_id);
    }

    function getClipByTiming(int $video_id, Request $request, ClipsService $clipsService){
        $validated = $request->validate([
            "timing" => "required|numeric",
        ]);

        return $clipsService->getClipByTiming($video_id, $validated["timing"]);
    }


    function getClipPaginated(int $video_id, ClipsService $clipsService){
        return $clipsService->getClipsPaginated($video_id);
    }

    function getClip(int $clip_id){
        $clip = Cache::get("clip_{$clip_id}");
        if ($clip){
            return $clip;
        }

        $clip = Clip::findOrFail($clip_id);

        Cache::set("clip_{$clip_id}", $clip);

        return $clip;

    }

}

