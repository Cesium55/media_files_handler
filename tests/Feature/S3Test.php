<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $bucket = config('filesystems.disks.s3.bucket');
    expect($bucket)->toBe('test-bucket');
});

afterAll(function () {
    $files = Storage::disk('s3')->allFiles('test-folder');
    Storage::disk('s3')->delete($files);
});

it('checks s3 test enviroment', function () {
    $bucket = config('filesystems.disks.s3.bucket');

    expect($bucket)->toBe('test-bucket');
});

it('uploads a file to s3', function () {
    $file = UploadedFile::fake()->create('example.txt', 1, 'text/plain');

    Storage::disk('s3')->putFileAs('test-folder', $file, 'example.txt');

    expect(Storage::disk('s3')->exists('test-folder/example.txt'))->toBeTrue();
});
