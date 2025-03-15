<?php

namespace App\Jobs;

use App\Models\Clip;
use App\Models\Video;
use App\Services\ProcessingLogsService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class HandleVideoJob implements ShouldQueue
{
    use Queueable;



    public Video $video;
    public int $start;

    /**
     * Create a new job instance.
     */
    public function __construct(Video $video, int $start = 0)
    {
        $this->video = $video;
        $this->start = $start;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->processVideo();
    }



    public function processVideo()
    {

        // new_intervals structure:

        //     [
        //         [
        //             "start" => float,
        //             "end" => float,
        //             "index_of_fragment" => int,
        //             "index_in_fragment" => int
        //         ],
        //     ]



        [$fragment_timings, $new_intervals] = $this->cutIntervals();

        $fragments_paths = $this->fragmentateVideo($fragment_timings);

        for ($i = 0; $i < count($new_intervals); $i++) {
            $start_time = microtime(true);
            $clipLocalPath = storage_path("app/temp/clip_{$i}.mp4");

            $clip_start = $new_intervals[$i]["start"];
            $clip_end = $new_intervals[$i]["end"];
            $fragment_index = $new_intervals[$i]["index_of_fragment"];
            $clip_fragment_index = $new_intervals[$i]["index_in_fragment"];

            $this->cutVideo(
                $fragments_paths[$fragment_index],
                $clipLocalPath,
                $clip_start,
                $clip_end
            );


            Log::channel("custom_log")->info("Clip $i before cutting");
            $clip = Clip::where("title", "clip_{$this->video->id}_{$i}")->first();
            Log::channel("custom_log")->info("Clip $i after cutting");

            $s3_clip_path = "clips/{$this->video->id}/clip_{$i}.mp4";
            $this->copy_to_s3($s3_clip_path, $clipLocalPath);

            $clip->video_path = $s3_clip_path;
            $clip->save();


            unlink($clipLocalPath);

            $end_time = microtime(true);
            $executionTime = $end_time - $start_time;

            ProcessingLogsService::log("clip", $clip->id, "video processed");
            ProcessingLogsService::log("video", $this->video->id, "clip {$i} processed({$executionTime})");
            Log::channel("custom_log")->info("Clip {$i} for video {$this->video->id} processed({$executionTime})");

        }


        ProcessingLogsService::log("video", $this->video->id, "all clips processed");

        $this->video->video_processed = true;
        $this->video->save();

        foreach ($fragments_paths as $path_to_unlink) {
            unlink($path_to_unlink);
        }

        rmdir($this->getOwnTempDir());
    }

    public function fragmentateVideo($fragment_timings)
    {
        $global_fragmentation_starttime = microtime(true);
        ProcessingLogsService::log("video", $this->video->id, "Fragmentation started");

        $local_video_path = $this->copy_to_local($this->video->video_path);

        $temp_dir = $this->getOwnTempDir();
        if(!file_exists($temp_dir)){
            mkdir($temp_dir, recursive: true);
        }


        $fragments_paths = [];

        for ($i = 0; $i < count($fragment_timings); $i++) {
            $local_fragmentation_starttime = microtime(true);

            $output_filename = $temp_dir . "/fragment_{$i}.mp4";

            $this->cutVideo($local_video_path, $output_filename, $fragment_timings[$i][0], $fragment_timings[$i][1]);

            $fragments_paths[] = $output_filename;

            $local_fragmentation_duration = microtime(true) - $local_fragmentation_starttime;
            ProcessingLogsService::log("video", $this->video->id, "Fragment {$i} was processed in {$local_fragmentation_duration} s");

        }
        $global_fragmetation_duration = microtime(true) - $global_fragmentation_starttime;
        ProcessingLogsService::log("video", $this->video->id, "Video was fragmentated in {$global_fragmetation_duration} s");
        unlink($local_video_path);
        return $fragments_paths;
    }

    public function getOwnTempDir(){
        return storage_path("app/temp/{$this->video->id}");
    }


    public function cutIntervals()
    {
        $old_intervals = $this->video->clip_intervals;
        $fragments_count = 1 + intdiv(count($old_intervals), 50);
        $approx_clip_per_fragment = intdiv(count($old_intervals), $fragments_count);

        $fragment_timings = array_fill(0, $fragments_count, false);
        $new_intervals = array_fill(0, count($old_intervals), []);

        $clips_allocated = 0;

        for ($current_fragment_index = 0; $current_fragment_index < $fragments_count; $current_fragment_index++) {

            $current_fragment_clips_count = 0;

            while (
                $clips_allocated < count($old_intervals) and
                (
                    $current_fragment_index + 1 == $fragments_count
                    or
                    $current_fragment_clips_count <= $approx_clip_per_fragment
                    or
                    $old_intervals[$clips_allocated][1] <= $old_intervals[$clips_allocated + 1][0]
                )
            ) {
                Log::channel("custom_log")->info("Clip allocated: $clips_allocated");
                $new_intervals[$clips_allocated]["index_of_fragment"] = $current_fragment_index;
                $new_intervals[$clips_allocated]["index_in_fragment"] = $current_fragment_clips_count;


                if ($fragment_timings[$current_fragment_index] === false) {
                    $fragment_timings[$current_fragment_index] = [
                        $old_intervals[$clips_allocated][0],
                        $old_intervals[$clips_allocated][1]
                    ];
                } else {
                    $fragment_timings[$current_fragment_index][1] = $old_intervals[$clips_allocated][1];
                }

                $clips_allocated++;
                $current_fragment_clips_count++;
            }

            if ($clips_allocated == count($old_intervals)) {
                break;
            }
        }

        Log::channel("custom")->info("Fragments for video[{$this->video->id}] generated($fragments_count)");

        for ($i = 0; $i < count($old_intervals); $i++) {
            $new_intervals[$i]["start"] = $old_intervals[$i][0] -
                $fragment_timings[$new_intervals[$i]["index_of_fragment"]][0];

            $new_intervals[$i]["end"] = $old_intervals[$i][1] -
                $fragment_timings[$new_intervals[$i]["index_of_fragment"]][0];
        }

        return [$fragment_timings, $new_intervals];


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
            mkdir($localDir, 0777, true);
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


    public function cutVideo($inputFile, $outputFile, $start, $end)
    {
        if (!file_exists($inputFile)) {
            throw new Exception("File $inputFile not found.");
        }

        $startFormatted = number_format($start, 3, '.', '');
        $duration = number_format($end - $start, 3, '.', '');

        if ($duration <= 0) {
            throw new Exception("Incorrect video length.");
        }

        $command = "ffmpeg -y -hwaccel cuda -hwaccel_output_format cuda -i " . escapeshellarg($inputFile) .
            " -ss " . escapeshellarg($startFormatted) .
            " -t " . escapeshellarg($duration) .
            " -c:v h264_nvenc -preset p4 -qp 23 -c:a aac -b:a 192k -movflags +faststart -reset_timestamps 1 " .
            escapeshellarg($outputFile) . " 2>&1";

        $output = shell_exec($command);

        if (!file_exists($outputFile) || filesize($outputFile) == 0) {
            throw new Exception("Error while video cutting: $output");
        }
    }
}
