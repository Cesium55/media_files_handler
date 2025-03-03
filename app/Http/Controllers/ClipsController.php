<?php

namespace App\Http\Controllers;

use App\Models\Clip;
use Illuminate\Http\Request;

class ClipsController extends Controller
{
    function get_clips(Request $request){
        $request->validate([
            "video_id" => "required|int|min:1"
        ]);
        return Clip::where("video_id", $request["video_id"])->get();
    }
}
