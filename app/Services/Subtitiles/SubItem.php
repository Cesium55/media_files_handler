<?php

namespace App\Services\Subtitiles;

class SubItem
{
    public int $index;

    public SubtitleTime $start_time;

    public SubtitleTime $end_time;

    public string $text;

    public function __construct(string $start_time, string $end_time, string $text, int $index = 0)
    {
        $this->index = $index;
        $this->start_time = new SubtitleTime($start_time);
        $this->end_time = new SubtitleTime($end_time);
        $this->text = $text;
    }

    public function getDuration(): int
    {
        return $this->end_time->getMilliseconds() - $this->start_time->getMilliseconds();
    }

    public function shift(int $milliseconds): void
    {
        $this->start_time->shift($milliseconds);
        $this->end_time->shift($milliseconds);
    }

    public function getClearedText(): string
    {
        return strip_tags($this->text);
    }

    public function __toString(): string
    {
        return "{$this->index}\n{$this->start_time} --> {$this->end_time}\n{$this->text}\n";
    }

    public function getStringTime()
    {
        return "{$this->start_time} --> {$this->end_time}";
    }
}
