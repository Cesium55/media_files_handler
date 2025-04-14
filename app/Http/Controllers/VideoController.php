<?php

namespace App\Http\Controllers;

use App\Http\Requests\VideoLoad;
use App\Jobs\HandleSubsJob;
use App\Jobs\HandleVideoDelete;
use App\Jobs\HandleVideoJob;
use App\Services\ProcessingLogsService;
use Illuminate\Http\Request;
use App\Models\Video;
use App\Http\Requests\VideoCreate;
use App\Http\Requests\SubsLoad;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Log;
use App\Services\VideoService;

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

    function load_subs(SubsLoad $request, int $video_id)
    {
        $validated = $request->validated();

        $video = Video::findOrFail($video_id);


        if ($video->is_subs_cut or $video->subs) {
            return response()->json([
                "message" => "Subtitiles for this video have been already cut"
            ], 409);
        }

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


        ProcessingLogsService::log("video", $video_id, "Loading subs");



        $data = [];

        Log::channel("custom_log")->info("Video with id={$video_id} loading subs");
        foreach ($request->file("files") as $file) {
            $language = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $path = Storage::disk('s3')->put("subtitles/videos/{$video_id}", $file);
            $data[$language] = $path;
        }

        $video->subs = $data;
        $video->save();

        HandleSubsJob::dispatch($video);

        $data["video_id"] = $video_id;
        return $video;

    }

    function load_video(VideoLoad $request, int $video_id)
    {
        // $request->validated();

        $video = Video::findOrFail($video_id);


        if (!$video->is_subs_cut){
            return response()->json([
                "message" => "Subs cutting required"
            ], 409);
        }

        if ($video->video_processed or $video->video_path) {
            ProcessingLogsService::log("video", $video->id, "Processing video error: video has been already processed");
            return response()->json([
                "message" => "Video already uploaded"
            ], 409);
        }


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


        ProcessingLogsService::log("video", $video->id, "Thumb and video loaded");

        HandleVideoJob::dispatch($video);

        return $video;
    }


    function delete(int $video_id)
    {
        $video = Video::findOrFail($video_id);

        HandleVideoDelete::dispatch($video);

        return response()->json([
            "message" => "Video will be deleted"
        ]);
    }

    function get_all()
    {
        return Video::all();
    }

    function recut(int $video_id){


        $video = Video::findOrFail($video_id);

        HandleVideoJob::dispatch($video);
    }



    public function get_video(int $video_id){


        $videoService = new VideoService();

        return $videoService->get($video_id);
    }

    public function get_video_logs(int $video_id){
        return ProcessingLogsService::get_logs("video", $video_id);
    }

    public function getAllPaginated(VideoService $videoService){
        return $videoService->getAllPaginated();
    }
}
