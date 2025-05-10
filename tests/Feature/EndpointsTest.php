<?php

use App\Models\Video;

it('creates and gets some videos', function () {
    $video1 = Video::factory()->create();
    $video2 = Video::factory()->create();
    $video3 = Video::factory()->create();

    $response = $this->get('/api/v1/videos');

    expect($response->status())->toBe(200);

    expect($response->getData()->data)->toBeArray()->toHaveLength(3);
});

it('creates video from http request', function () {

    config(['app.debug' => true]);
    config(['auth.debug_disable_auth' => true]);

    $data = [
        'title' => 'QWEerty zxc',
        'description' => 'n9y7834ct y793tg 47h983g 47h8934fg hg 783b5gv byui35v 7n 89b35v  7n8935v 897n',
        'language' => 'eng',
    ];

    $response = $this->post('/api/v1/videos/video', $data);

    $video = Video::where('description', $data['description'])->first();

    expect($response->status())->toBe(201);

    $response_data = $response->getData();
    expect($response_data)
        ->title->toBe($data['title'])
        ->description->toBe($data['description'])
        ->language->toBe($data['language']);

    expect($video)->toBeObject();
    expect($video->id)->toBe($response_data->id);
});
