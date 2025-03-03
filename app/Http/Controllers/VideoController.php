<?php

namespace App\Http\Controllers;

use App\Http\Requests\VideoLoad;
use App\Jobs\HandleSubsJob;
use App\Jobs\HandleVideoJob;
use App\Services\ProcessingLogsService;
use Illuminate\Http\Request;
use App\Models\Video;
use App\Http\Requests\VideoCreate;
use App\Http\Requests\SubsLoad;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Log;
use ReturnTypeWillChange;

class VideoController extends Controller
{
    function create(VideoCreate $request)
    {
        $data = $request->validated();

        $video = Video::create([
            "title" => $data["title"],
            "description" => $data["description"],
            "language" => $data["language"]
        ]);

        return $video;
    }

    function load_subs(SubsLoad $request)
    {
        $validated = $request->validated();

        $video = Video::findOrFail($request["video_id"]);
        $has_original_subs = false;

        foreach ($request->file("files") as $file) {
            $language = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            Log::channel("custom_log")->info("lang: {$language}");
            if ($language == $video->language) {
                $has_original_subs = true;
                break;
            }
        }
        if (!$has_original_subs) {
            return response()->json([
                "message" => "Substitles for this video must consist {$video->language} sub file"
            ], 400);
        }


        ProcessingLogsService::log("video", $request["video_id"], "Loading subs");

        if ($video->is_subs_cut and config("app.debug") == false) {
            return response()->json([
                "message" => "Subtitiles for this video have been already cut"
            ], 409);
        }

        $data = [];

        Log::channel("custom_log")->info("Video with id={$request['video_id']} loading subs");
        foreach ($request->file("files") as $file) {
            $language = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $path = Storage::disk('s3')->put("subtitles/videos/{$request['video_id']}", $file);
            $data[$language] = $path;
        }

        $video->subs = $data;
        $video->save();

        HandleSubsJob::dispatch($video);

        $data["video_id"] = $request["video_id"];
        return $video;

    }

    function load_video(VideoLoad $request)
    {
        // $request->validated();

        $video = Video::findOrFail($request["video_id"]);

        $thumb_path = Storage::disk("s3")->put(
            "thumbs/$video->id",
            $request->file("thumb")
        );

        $video_path = Storage::disk("s3")->put(
            "videos/$video->id",
            $request->file("video")
        );

        $video->video_path = $video_path;
        $video->thumb_path = $thumb_path;

        $video->save();

        HandleVideoJob::dispatch($video);

        return $video;
    }

    function get_all()
    {
        return Video::all();
    }
}
