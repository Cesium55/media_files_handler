<?php

namespace App\Services;

use App\Models\Clip;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ClipsService{



    public function getVideoClips(int $video_id){
        $clips = Cache::get("video_clips" . $video_id);

        if ($clips != null){
            return $clips;
        }

        $clips = Clip::where("video_id", $video_id)->get();

        Cache::set("video_clips" . $video_id, $clips, 600);

        return $clips;

    }

}
