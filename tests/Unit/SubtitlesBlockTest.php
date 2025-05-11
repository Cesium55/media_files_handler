<?php

use App\Services\Subtitiles\SubItem;
use App\Services\Subtitiles\SubtitlesBlock;

function makeSub($start, $end, $text, $index = 0): SubItem
{
    return new SubItem($start, $end, $text, $index);
}

it('can add subtitles and count them', function () {
    $block = new SubtitlesBlock;
    expect($block)->toHaveCount(0);

    $block->addSub(makeSub('00:00:01,000', '00:00:02,000', 'First'));
    $block->addSub(makeSub('00:00:03,000', '00:00:04,000', 'Second'));

    expect($block)->toHaveCount(2);
});

it('can shift all subtitles', function () {
    $block = new SubtitlesBlock;
    $block->addSub(makeSub('00:00:01,000', '00:00:02,000', 'Shift me'));
    $block->shift(1000);

    expect((string) $block)->toContain('00:00:02,000 --> 00:00:03,000');
});

it('can pop the last subtitle', function () {
    $block = new SubtitlesBlock;
    $block->addSub(makeSub('00:00:01,000', '00:00:02,000', 'A'));
    $block->addSub(makeSub('00:00:03,000', '00:00:04,000', 'B'));

    $popped = $block->subs_pop();

    expect($popped->text)->toBe('B')
        ->and($block)->toHaveCount(1);
});

it('can recount time relative to first subtitle', function () {
    $block = new SubtitlesBlock;
    $block->addSub(makeSub('00:01:00,000', '00:01:02,000', 'A'));
    $block->addSub(makeSub('00:01:05,000', '00:01:06,000', 'B'));

    $block->recountTime();

    $subs = $block->subs;

    expect((string) $subs[0]->start_time)->toBe('00:00:00,000')
        ->and((string) $subs[0]->end_time)->toBe('00:00:02,000')
        ->and((string) $subs[1]->start_time)->toBe('00:00:05,000')
        ->and((string) $subs[1]->end_time)->toBe('00:00:06,000');

    expect($subs[0]->index)->toBe(1)
        ->and($subs[1]->index)->toBe(2);
});

it('converts block to string correctly', function () {
    $block = new SubtitlesBlock;
    $block->addSub(new SubItem('00:00:01,000', '00:00:02,000', 'Hello', 1));

    expect((string) $block)->toContain("1\n00:00:01,000 --> 00:00:02,000\nHello\n");
});

it('calculates total time of all subtitles', function () {
    $block = new SubtitlesBlock;
    $block->addSub(makeSub('00:00:05,000', '00:00:06,500', 'One'));
    $block->addSub(makeSub('00:00:07,000', '00:00:10,000', 'Two'));

    expect($block->getTotalTime())->toBe(5000);
});

it('returns null for total time when empty', function () {
    $block = new SubtitlesBlock;
    expect($block->getTotalTime())->toBeNull();
});
