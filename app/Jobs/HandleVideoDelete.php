<?php

namespace App\Jobs;

use App\Models\Clip;
use App\Models\Video;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\ProcessingLogsService;

class HandleVideoDelete implements ShouldQueue
{
    use Queueable;


    public Video $video;

    /**
     * Create a new job instance.
     */
    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            ProcessingLogsService::log("video", $this->video->id, "Video deleting started");
            Log::channel("custom_log")->info("Video deleting started");

            $clips = Clip::where("video_id", $this->video->id)->get();


            Log::channel("custom_log")->info("Got " . count($clips) . " clips");

            foreach ($clips as $clip) {
                Log::channel("custom_log")->info("Deleting clip");
                if ($clip->video_path and Storage::disk("s3")->exists($clip->video_path)) {
                    Log::channel("custom_log")->info(message: "Deleting clip video");
                    Storage::disk("s3")->delete($clip->video_path);
                }
                foreach ($clip->subs as $lang => $path) {
                    if ($path and Storage::disk("s3")->exists($path)) {
                        Log::channel("custom_log")->info(message: "Deleting clip sub");
                        Storage::disk("s3")->delete($path);
                    }
                }

                Log::channel("custom_log")->info("Clip deleted");
            }

            Clip::where("video_id", $this->video->id)->delete();

            ProcessingLogsService::log("video", $this->video->id, "Clips deleted");


            if ($this->video->video_path and Storage::disk("s3")->exists($this->video->video_path)) {
                Storage::disk("s3")->delete($this->video->video_path);
            }

            if ($this->video->thumb_path and Storage::disk("s3")->exists($this->video->thumb_path)) {
                Storage::disk("s3")->delete($this->video->thumb_path);
            }

            foreach ($this->video->subs as $lang => $path) {
                if ($path and Storage::disk("s3")->exists($path)) {
                    Storage::disk("s3")->delete($path);
                }
            }

            $this->video->delete();

            ProcessingLogsService::log("video", $this->video->id, "Video deleted");


        } catch (Exception $ex) {

            ProcessingLogsService::log("video", $this->video->id, "Unkown error while video deleting");
        }
    }
}
