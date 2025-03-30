<?php

namespace App\Http\Controllers;

use App\Models\Clip;
use App\Services\ClipsService;
use Illuminate\Http\Request;

class ClipsController extends Controller
{
    // function get_clips(int $video_id){
    //     return Clip::where("video_id", $video_id)->get();
    // }


    function get_clips(int $video_id, ClipsService $clipsService){
        return $clipsService->getVideoClips($video_id);
    }

    function getClipByTiming(Request $request, ClipsService $clipsService){
        $validated = $request->validate([
            "timing" => "required|numeric",
            "video_id" => "required|int|min:1"
        ]);

        return $clipsService->getClipByTiming($validated["video_id"], $validated["timing"]);
    }
}

