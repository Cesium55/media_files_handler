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

                Log::channel("custom_log")->info(message: "Deleting clip video");
                HandleVideoDelete::softDeleteFile($clip->video_path);


                foreach ($clip->subs as $lang => $path) {
                    Log::channel("custom_log")->info(message: "Deleting clip sub");
                    HandleVideoDelete::softDeleteFile($path);
                }

                Log::channel("custom_log")->info("Clip deleted");
            }

            Clip::where("video_id", $this->video->id)->delete();

            ProcessingLogsService::log("video", $this->video->id, "Clips deleted");


            HandleVideoDelete::softDeleteFile($this->video->video_path);

            HandleVideoDelete::softDeleteFile($this->video->thumb_path);

            if ($this->video->subs) {
                foreach ($this->video->subs as $lang => $path) {
                    HandleVideoDelete::softDeleteFile($path);
                }
            }

            $this->video->delete();

            ProcessingLogsService::log("video", $this->video->id, "Video deleted");


        } catch (Exception $ex) {

            Log::channel("custom_log")->info($ex);
            ProcessingLogsService::log("video", $this->video->id, "Unkown error while video deleting");
        }
    }


    private static function softDeleteFile($path)
    {
        if (!is_string($path)) {
            return;
        }
        if (Storage::disk("s3")->exists($path)) {
            Storage::disk("s3")->delete($path);
        }
    }
}
