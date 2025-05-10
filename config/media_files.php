<?php

return [
    'allowed_langs' => ['eng', 'rus', 'ita', 'fra', 'deu', 'spa'],
    'clip_duration' => (float) env('CLIP_DURATION', 10),
    'clip_padding' => (float) env('CLIP_PADDING', 0.5),
    'gpu_handling' => (bool) env('GPU_HANDLING', false),
];
