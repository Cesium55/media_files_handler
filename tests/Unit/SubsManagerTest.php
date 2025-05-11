<?php

use App\Services\Subtitiles\SubsManager;
use Mockery;
use Psr\Log\LoggerInterface;

beforeEach(function () {

    $this->srt = <<<'SRT'
1
00:00:01,000 --> 00:00:03,000
Hello

2
00:00:04,000 --> 00:00:06,000
World

3
00:00:07,000 --> 00:00:09,000
Foo
SRT;
});

it('parses SRT correctly', function () {

    $loggerMock = Mockery::mock(LoggerInterface::class);
    $loggerMock->shouldReceive('info');

    $manager = new SubsManager($this->srt, $loggerMock);

    expect(count($manager->subs))->toBe(3);
    expect($manager->subs[0]->text)->toBe('Hello');
    expect($manager->subs[1]->text)->toBe('World');
    expect($manager->subs[2]->text)->toBe('Foo');
});

it('splits subtitles correctly with max interval', function () {

    $loggerMock = Mockery::mock(LoggerInterface::class);
    $loggerMock->shouldReceive('info');

    $manager = new SubsManager($this->srt, $loggerMock);

    [$intervals, $blocks] = $manager->getSplitting(5.0);

    expect(count($blocks))->toBe(2);

    expect($intervals[0][0])->toBe(1);
    expect($intervals[0][1])->toBe(6);

    expect($intervals[1][0])->toBe(7);
    expect($intervals[1][1])->toBe(9);
});

it('splits subtitles with padding correctly', function () {

    $loggerMock = Mockery::mock(LoggerInterface::class);
    $loggerMock->shouldReceive('info');

    $manager = new SubsManager($this->srt, $loggerMock);

    [$intervals, $blocks] = $manager->getSplittingWithPadding(5.0, 0.5);

    expect($intervals[0][0])->toBe(0.5);
    expect($intervals[0][1])->toBe(6.5);

    expect($blocks[0]->subs[0]->start_time->getMilliseconds())->toBe(500);
});

it('compares subtitles correctly', function () {

    $loggerMock = Mockery::mock(LoggerInterface::class);
    $loggerMock->shouldReceive('info');

    $manager1 = new SubsManager($this->srt, $loggerMock);
    $manager2 = new SubsManager($this->srt, $loggerMock);

    expect($manager1->compareTimings($manager2))->toBeTrue();
});

it('returns false if subtitles do not match', function () {

    $loggerMock = Mockery::mock(LoggerInterface::class);
    $loggerMock->shouldReceive('info');

    $srt2 = <<<'SRT'
1
00:00:01,000 --> 00:00:03,000
Hello

2
00:00:04,000 --> 00:00:07,000
World
SRT;

    $manager1 = new SubsManager($this->srt, $loggerMock);
    $manager2 = new SubsManager($srt2, $loggerMock);

    expect($manager1->compareTimings($manager2))->toBeFalse();
});
