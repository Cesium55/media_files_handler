<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanTempDirCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-temp-dir';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear storage/app/temp directory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tempPath = storage_path('app/temp');

        if (!File::exists($tempPath)) {
            $this->info('Dir temp does not exists');
            return;
        }

        File::cleanDirectory($tempPath);
        $this->info('Dir temp cleaned');
    }
}
