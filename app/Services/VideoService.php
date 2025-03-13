<?php

namespace App\Services;

use App\Models\Video;


class VideoService{


    public function get(int $id){
        $video = Video::find($id);
        if (!$video){
            return response()->json([
                "message" => "Video [id=$id] not found"
            ], 404);
        }

        return $video;
    }
}
