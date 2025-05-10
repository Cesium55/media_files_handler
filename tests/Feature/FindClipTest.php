<?php

namespace Tests\Feature;

use App\Services\ClipsService;
use PHPUnit\Framework\TestCase;

class FindClipTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_timings_binary_search(): void
    {

        $clipsService = new ClipsService;

        $segments = [
            [16.875, 25.625],
            [27.875, 35.167],
            [34.167, 44.75],
            [43.75, 54.167],
        ];

        $this->assertEquals(0, $clipsService->timingBinarySearch($segments, 20)); // Число внутри первого отрезка
        $this->assertEquals(1, $clipsService->timingBinarySearch($segments, 30)); // Число внутри второго отрезка
        $this->assertEquals(2, $clipsService->timingBinarySearch($segments, 40)); // Число внутри третьего отрезка
        $this->assertEquals(3, $clipsService->timingBinarySearch($segments, 50)); // Число внутри четвертого отрезка
        $this->assertEquals(-1, $clipsService->timingBinarySearch($segments, 60)); // Число вне всех отрезков

        print_r('result : '.$clipsService->timingBinarySearch($segments, 34.5));

        // Тест на пересечение
        $this->assertEquals(1, $clipsService->timingBinarySearch($segments, 34.5)); // Число в пересечении, но глубже в третьем
    }
}
