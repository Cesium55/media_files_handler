<?php

use App\Services\Subtitiles\SubItem;

it('creates a SubItem correctly', function () {
    $sub = new SubItem('00:00:01,000', '00:00:03,000', 'Test text', 1);

    expect($sub->index)->toBe(1)
        ->and((string) $sub->start_time)->toBe('00:00:01,000')
        ->and((string) $sub->end_time)->toBe('00:00:03,000')
        ->and($sub->text)->toBe('Test text');
});

it('calculates correct duration', function () {
    $sub = new SubItem('00:00:01,000', '00:00:03,500', 'Some text');
    expect($sub->getDuration())->toBe(2500);
});

it('shifts start and end time forward', function () {
    $sub = new SubItem('00:00:10,000', '00:00:12,000', 'Shifting');

    $sub->shift(2000);
    expect((string) $sub->start_time)->toBe('00:00:12,000')
        ->and((string) $sub->end_time)->toBe('00:00:14,000');
});

it('shifts start and end time backward', function () {
    $sub = new SubItem('00:00:10,000', '00:00:12,000', 'Backwards');

    $sub->shift(-3000);

    expect((string) $sub->start_time)->toBe('00:00:07,000')
        ->and((string) $sub->end_time)->toBe('00:00:09,000');
});

it('clears HTML from text', function () {
    $sub = new SubItem('00:00:01,000', '00:00:02,000', '<b>Hello</b> <i>World</i>');
    expect($sub->getClearedText())->toBe('Hello World');
});

it('returns correct string representation', function () {
    $sub = new SubItem('00:00:01,000', '00:00:03,000', 'Subtitle line', 2);

    $expected = "2\n00:00:01,000 --> 00:00:03,000\nSubtitle line\n";

    expect((string) $sub)->toBe($expected);
});

it('returns string time range correctly', function () {
    $sub = new SubItem('00:00:02,000', '00:00:04,000', 'Example');

    expect($sub->getStringTime())->toBe('00:00:02,000 --> 00:00:04,000');
});
