<?php

use App\Services\Subtitiles\SubtitleTime;

it('converts time string to milliseconds correctly', function () {
    $time = new SubtitleTime('00:01:02,500');
    expect($time->getMilliseconds())->toBe(62500);
});

it('shifts milliseconds forward correctly', function () {
    $time = new SubtitleTime('00:00:01,000');
    $time->shift(500);
    expect($time->getMilliseconds())->toBe(1500);
});

it('shifts milliseconds backward correctly', function () {
    $time = new SubtitleTime('00:00:02,000');
    $time->shift(-1000);
    expect($time->getMilliseconds())->toBe(1000);
});

it('converts milliseconds back to time string correctly', function () {
    $time = new SubtitleTime('01:02:03,004');
    expect((string) $time)->toBe('01:02:03,004');
});

it('handles zero time correctly', function () {
    $time = new SubtitleTime('00:00:00,000');
    expect($time->getMilliseconds())->toBe(0)
        ->and((string) $time)->toBe('00:00:00,000');
});

it('maintains precision with multiple shifts', function () {
    $time = new SubtitleTime('00:00:10,000');
    $time->shift(1500);
    $time->shift(-500);
    expect((string) $time)->toBe('00:00:11,000');
});
