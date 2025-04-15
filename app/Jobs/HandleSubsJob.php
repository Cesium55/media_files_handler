<?php

namespace App\Jobs;

use App\Events\VideoCreated;
use App\Models\Clip;
use App\Models\Video;
use App\Services\ProcessingLogsService;
use App\Services\Subtitiles\SubsManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class HandleSubsJob implements ShouldQueue
{
    use Queueable;

    public Video $video;
    public function __construct($video)
    {
        $this->video = $video;
    }

    public function handle(): void
    {
        $this->process();
        VideoCreated::dispatch($this->video->id);
    }


    public function process(){
        Log::channel("custom_log")
        ->info("Starting subs processing for video[id={$this->video->id}]");

    ProcessingLogsService::log("video", $this->video->id, "Starting subs processing");

    $subs_managers = $this->get_sub_managers();
    if (!$subs_managers)
        return;

    $split = [];

    foreach ($subs_managers as $lang => $manager) {
        [$splitting, $blocks] = $manager->getSplittingWithPadding(
            config("media_files.clip_duration"),
            config("media_files.clip_padding")
        );
        $split[$lang] = $blocks;
    }

    for ($i = 0; $i < count($splitting); $i++) {
        $subs = [];
        foreach ($split as $lang => $blocks) {
            $path = "subtitles/clips/{$this->video->id}/$lang/$i.srt";
            $subs[$lang] = $path;
            Storage::disk('s3')->put($path, (string) $blocks[$i]);
        }

        Clip::create([
            "title" => "clip_" . $this->video->id . "_" . $i,
            "video_id" => $this->video->id,
            "subs" => $subs
        ]);

        ProcessingLogsService::log("video", $this->video->id, "Clip $i added");
    }

    $this->video->clip_intervals = $splitting;
    $this->video->is_subs_cut = true;
    $this->video->save();

    ProcessingLogsService::log("video", $this->video->id, "Video subs Processed");
    }


    private function get_sub_managers()
    {

        $original_subs_manager = new SubsManager(
            Storage::disk("s3")->get($this->video->subs[$this->video->language])
        );

        $subs_managers = [$this->video->language => $original_subs_manager];
        foreach ($this->video->subs as $lang => $path) {
            if ($lang == $this->video->language)
                continue;
            Log::channel("custom_log")->info("Handling lang: {$lang}");

            $sm = new SubsManager(
                Storage::disk("s3")->get($path)
            );

            if (!$original_subs_manager->compareTimings($sm)) {
                Log::channel("custom_log")
                    ->error("Error: {$this->video->language} and $lang have different timings");
                ProcessingLogsService::log(
                    "video",
                    $this->video->id,
                    "Error: {$this->video->language} and $lang have different timings"
                );
                return false;
            }

            $subs_managers[$lang] = $sm;
        }

        ProcessingLogsService::log(
            "video",
            $this->video->id,
            "All timings checked"
        );
        return $subs_managers;
    }
}
