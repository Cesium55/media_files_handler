<?php

use PHPUnit\Framework\TestCase;

use App\Services\Subtitiles\SubtitleTime;
use App\Services\Subtitiles\SubItem;
use App\Services\Subtitiles\SubtitlesBlock;
use App\Services\Subtitiles\SubsManager;


class SubtitlesTest extends TestCase {
    public function testSubtitleTime() {
        $time = new SubtitleTime("00:01:30,500");
        $this->assertEquals("00:01:30,500", (string) $time);

        $time->shift(1500);
        $this->assertEquals("00:01:32,000", (string) $time);
    }

    public function testSubItem() {
        $sub = new SubItem("00:00:05,000", "00:00:07,000", "Hello, world!");
        $this->assertEquals(2000, $sub->getDuration());

        $sub->shift(1000);
        $this->assertEquals("00:00:06,000", (string) $sub->start_time);
        $this->assertEquals("00:00:08,000", (string) $sub->end_time);
    }

    public function testSubtitlesBlock() {
        $block = new SubtitlesBlock();
        $block->addSub(new SubItem("00:00:05,000", "00:00:07,000", "Hello"));
        $block->addSub(new SubItem("00:00:10,000", "00:00:12,000", "World"));

        $block->shift(2000);
        $this->assertEquals("00:00:07,000", (string) $block->subs[0]->start_time);
        $this->assertEquals("00:00:12,000", (string) $block->subs[1]->start_time);

        $block->recountTime();
        $this->assertEquals("00:00:00,000", (string) $block->subs[0]->start_time);
    }

    public function testSubsManager() {
        $srtContent = <<<SRT
1
00:00:05,000 --> 00:00:07,000
Hello

2
00:00:10,000 --> 00:00:12,000
World
SRT;

        $manager = new SubsManager($srtContent);
        [$intervals, $blocks] = $manager->getSplitting(5.0);

        $this->assertCount(2, $intervals);
        $this->assertCount(2, $blocks);

        [$intervalsWithPadding, $blocksWithPadding] = $manager->getSplittingWithPadding(5.0, 0.5);
        $this->assertEquals(4.5, $intervalsWithPadding[0][0]);
        $this->assertEquals(12.5, $intervalsWithPadding[1][1]);

        $manager2 = new SubsManager($srtContent);
        $this->assertTrue($manager->compareTimings($manager2));
    }
}
