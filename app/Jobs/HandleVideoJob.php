<?php

namespace App\Jobs;

use App\Models\Clip;
use App\Models\Video;
use App\Services\ProcessingLogsService;
use Exception;
use FFMpeg\FFProbe\DataMapping\Format;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Log;

class HandleVideoJob implements ShouldQueue
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

        try{
            ProcessingLogsService::log("video", $this->video->id, "Video cutting started");

            $ffmpeg = FFMpeg::create();
            $local_video_path = $this->copy_to_local($this->video->video_path);
            $video_ffmeg = $ffmpeg->open($local_video_path);

            $intervals = $this->video->clip_intervals;

            ProcessingLogsService::log(
                "video",
                $this->video->id,
                "Intervals created (" . count($intervals) . " intervals)");

            for ($i = 0; $i < count($intervals); $i++) {
                $clipLocalPath = storage_path("app/temp/clip_{$i}.mp4");
                $video_ffmeg->filters()->clip(
                    \FFMpeg\Coordinate\TimeCode::fromSeconds($intervals[$i][0]),
                    \FFMpeg\Coordinate\TimeCode::fromSeconds($intervals[$i][1] - $intervals[$i][0])
                );

                $video_ffmeg->save(new X264(), $clipLocalPath);

                $clip = Clip::where("title", "clip_{$this->video->id}_{$i}")->first();

                $s3_clip_path = "clips/{$this->video->id}/clip_{$i}.mp4";
                $this->copy_to_s3($s3_clip_path, $clipLocalPath);

                $clip->video_path = $s3_clip_path;
                $clip->save();


                unlink($clipLocalPath);

                ProcessingLogsService::log("clip", $clip->id, "video processed");
                ProcessingLogsService::log("video", $this->video->id, "clip {$i} processed");
                Log::channel("custom_log")->info("Clip {$i} for video {$this->video->id} processed");
            }

            ProcessingLogsService::log("video", $this->video->id, "all clips processed");

            $this->video->video_processed = true;
            $this->video->save();

            unlink($local_video_path);
        }
        catch (Exception $ex) {
            ProcessingLogsService::log("video", $this->video->id, "unknown error while cutting video");
        }

    }

    public function copy_to_local($s3_path)
    {
        $temp_name = Str::uuid();
        $localPath = storage_path("app/temp/$temp_name.mp4");

        Log::channel("custom_log")->info("Trying to copy from s3");
        Log::channel("custom_log")->info("From " . $s3_path);
        Log::channel("custom_log")->info("To   " . $localPath);



        // Storage::disk('s3')->download($s3_path, $localPath);
        $localDir = dirname($localPath);
        if (!file_exists($localDir)) {
            mkdir($localDir, 0777, true); // Рекурсивное создание папок
        }

        file_put_contents(
            $localPath,
            Storage::disk("s3")->get($s3_path),

        );

        return $localPath;
    }

    public function copy_to_s3($s3_path, $local_path)
    {
        Storage::disk('s3')->put($s3_path, file_get_contents($local_path));
    }
}
