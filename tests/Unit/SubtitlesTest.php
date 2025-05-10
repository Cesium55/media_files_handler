<?php

use App\Services\Subtitiles\SubItem;
use App\Services\Subtitiles\SubtitlesBlock;
use App\Services\Subtitiles\SubtitleTime;
use PHPUnit\Framework\TestCase;

class SubtitlesTest extends TestCase
{
    public function test_subtitle_time()
    {
        $time = new SubtitleTime('00:01:30,500');
        $this->assertEquals('00:01:30,500', (string) $time);

        $time->shift(1500);
        $this->assertEquals('00:01:32,000', (string) $time);
    }

    public function test_sub_item()
    {
        $sub = new SubItem('00:00:05,000', '00:00:07,000', 'Hello, world!');
        $this->assertEquals(2000, $sub->getDuration());

        $sub->shift(1000);
        $this->assertEquals('00:00:06,000', (string) $sub->start_time);
        $this->assertEquals('00:00:08,000', (string) $sub->end_time);
    }

    public function test_subtitles_block()
    {
        $block = new SubtitlesBlock;
        $block->addSub(new SubItem('00:00:05,000', '00:00:07,000', 'Hello'));
        $block->addSub(new SubItem('00:00:10,000', '00:00:12,000', 'World'));

        $block->shift(2000);
        $this->assertEquals('00:00:07,000', (string) $block->subs[0]->start_time);
        $this->assertEquals('00:00:12,000', (string) $block->subs[1]->start_time);

        $block->recountTime();
        $this->assertEquals('00:00:00,000', (string) $block->subs[0]->start_time);
    }
}
