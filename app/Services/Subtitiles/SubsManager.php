<?php

namespace App\Services\Subtitiles;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class SubsManager
{
    public array $subs = [];

    private LoggerInterface $logger;

    public function __construct(string $srtContent, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? Log::getLogger();
        $this->parseSrt($srtContent);
    }

    public function parseSrt(string $srtContent): void
    {
        $blocks = preg_split("/\r?\n\r?\n/", trim($srtContent));
        $this->logger->info('Blocks count: '.count($blocks));

        foreach ($blocks as $block) {
            $lines = preg_split("/\r?\n/", $block);
            if (count($lines) < 3) {
                continue;
            }

            $index = (int) array_shift($lines);
            $timecodes = array_shift($lines);

            if (! preg_match("/(\d{2}:\d{2}:\d{2},\d{3}) --> (\d{2}:\d{2}:\d{2},\d{3})/", $timecodes, $matches)) {
                continue;
            }

            [$full, $start, $end] = $matches;
            $text = implode("\n", $lines);

            $this->subs[] = new SubItem($start, $end, $text, $index);
        }
    }

    public function getSplitting(float $maxInterval = 10.0): array
    {
        $intervals = [];
        $blocks = [];
        $i = 0;

        while ($i < count($this->subs)) {
            $block = new SubtitlesBlock;
            $startTime = $this->subs[$i]->start_time->getMilliseconds() / 1000;

            while ($i < count($this->subs)) {
                $block->addSub($this->subs[$i]);
                $i++;
                $this->logger->info("Current time: {$block->getTotalTime()}");

                if ($block->getTotalTime() / 1000 > $maxInterval) {
                    break;
                }
            }

            if (count($block) > 1 && ($block->getTotalTime() / 1000 > $maxInterval)) {
                $i--;
                $block->subs_pop();
            }

            $endTime = end($block->subs)->end_time->getMilliseconds() / 1000;
            $block->recountTime();
            $intervals[] = [$startTime, $endTime];
            $blocks[] = $block;
        }

        return [$intervals, $blocks];
    }

    public function getSplittingWithPadding(float $maxInterval = 10.0, float $padding = 0.5): array
    {
        [$intervals, $blocks] = $this->getSplitting($maxInterval);

        foreach ($intervals as $i => &$interval) {
            if ($interval[0] >= $padding) {
                $interval[0] -= $padding;
                $interval[1] += $padding;
                $blocks[$i]->shift($padding * 1000);
            }
        }

        return [$intervals, $blocks];
    }

    public function compareTimings(SubsManager $other): bool
    {
        if (count($this->subs) !== count($other->subs)) {
            $this->logger->info('Subs have different timing count');

            return false;
        }
        $count = count($this->subs);
        $this->logger->info("Subs have same counts: {$count}");

        foreach ($this->subs as $i => $sub) {
            $myTime = $sub->getStringTime();
            $otherTime = $other->subs[$i]->getStringTime();

            $this->logger->info("comparing ↓↓↓ {$myTime}");
            $this->logger->info("comparing ↑↑↑ {$otherTime}");

            if ($myTime !== $otherTime) {
                return false;
            }
        }

        return true;
    }
}
