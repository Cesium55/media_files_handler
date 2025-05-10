<?php

namespace App\Jobs\base;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

abstract class ClearTempFilesBaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected string $jobCacheKey;

    private function getCacheKey(): string
    {
        return 'job_temp_files:'.$this->jobCacheKey;
    }

    public function __construct()
    {
        $this->jobCacheKey = 'job_temp_files:'.uniqid();
    }

    public function addTempFile(string $filePath): void
    {

        $files = cache()->get($this->getCacheKey(), []);
        $files = Cache::get($this->getCacheKey(), []);
        $files[] = $filePath;
        Cache::set($this->getCacheKey(), $files);

    }

    protected function deleteTempFiles(): void
    {
        $files = cache()->get($this->getCacheKey(), []);

        foreach ($files as $filePath) {
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        Cache::forget($this->getCacheKey());
    }

    public function failed(Throwable $exception): void
    {
        $this->deleteTempFiles();
    }
}
