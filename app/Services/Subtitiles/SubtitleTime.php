<?php

namespace App\Services\Subtitiles;

class SubtitleTime
{
    private int $milliseconds;

    public function __construct(string $time)
    {
        $this->milliseconds = $this->timeToMilliseconds($time);
    }

    public function shift(int $milliseconds): void
    {
        $this->milliseconds += $milliseconds;
    }

    public function getMilliseconds(): int
    {
        return $this->milliseconds;
    }

    public function __toString(): string
    {
        return $this->millisecondsToTime($this->milliseconds);
    }

    private function timeToMilliseconds(string $time): int
    {
        sscanf($time, '%d:%d:%d,%d', $hours, $minutes, $seconds, $milliseconds);

        return (($hours * 3600 + $minutes * 60 + $seconds) * 1000) + $milliseconds;
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
}
