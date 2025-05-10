<?php

namespace App\Services\Subtitiles;

use Countable;

class SubtitlesBlock implements Countable
{
    public array $subs = [];

    public function addSub(SubItem $sub): void
    {
        $this->subs[] = $sub;
    }

    public function shift(int $milliseconds): void
    {
        foreach ($this->subs as $sub) {
            $sub->shift($milliseconds);
        }
    }

    public function subs_pop()
    {
        return array_pop($this->subs);
    }

    public function recountTime(): void
    {
        if (empty($this->subs)) {
            return;
        }

        $firstSubStart = $this->subs[0]->start_time->getMilliseconds();

        foreach ($this->subs as $index => $sub) {
            $newStart = $sub->start_time->getMilliseconds() - $firstSubStart;
            $newEnd = $sub->end_time->getMilliseconds() - $firstSubStart;
            $this->subs[$index] = new SubItem(
                $this->millisecondsToTime($newStart),
                $this->millisecondsToTime($newEnd),
                $sub->getClearedText(),
                $index + 1,
            );
        }
    }

    public function __toString(): string
    {
        return implode("\n", array_map(fn ($sub) => (string) $sub, $this->subs));
    }

    public function count(): int
    {
        return count($this->subs);
    }

    private function millisecondsToTime(int $milliseconds): string
    {
        $hours = intdiv($milliseconds, 3600000);
        $milliseconds %= 3600000;
        $minutes = intdiv($milliseconds, 60000);
        $milliseconds %= 60000;
        $seconds = intdiv($milliseconds, 1000);
        $milliseconds %= 1000;

        return sprintf('%02d:%02d:%02d,%03d', $hours, $minutes, $seconds, $milliseconds);
    }

    public function getTotalTime()
    {
        if (empty($this->subs)) {
            return;
        }
        $startTime = $this->subs[0]->start_time->getMilliseconds();

        return end($this->subs)->end_time->getMilliseconds() - $startTime;
    }
}
