<?php

namespace App\Http\Controllers;

use App\Models\Clip;
use Illuminate\Http\Request;

class ClipsController extends Controller
{
    function get_clips(int $video_id){
        return Clip::where("video_id", $video_id)->get();
    }
}
