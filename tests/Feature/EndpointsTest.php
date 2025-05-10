<?php

use App\Models\Clip;
use App\Models\Video;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    config(['app.debug' => true]);
    config(['auth.debug_disable_auth' => true]);

});

beforeAll(function () {

    $videoPath = __DIR__.'/../Files/temp/test_video.mp4';
    $command = "ffmpeg -f lavfi -i testsrc=duration=40:size=1280x720:rate=30 -c:v libx264 -pix_fmt yuv420p {$videoPath} -y > /dev/null 2> /dev/null";
    exec($command);

});

afterAll(function () {
    $files = Storage::disk('s3')->allFiles();
    Storage::disk('s3')->delete($files);

    $videoPath = base_path('tests/Files/temp/test_video.mp4');
    if (file_exists($videoPath)) {
        unlink($videoPath);
    }

});

it('creates and gets some videos', function () {
    $video1 = Video::factory()->create();
    $video2 = Video::factory()->create();
    $video3 = Video::factory()->create();

    $response = $this->get('/api/v1/videos');

    expect($response->status())->toBe(200);

    expect($response->getData()->data)->toBeArray()->toHaveLength(3);
});

it('creates video via http', function () {

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

it('load subs to video', function () {
    $video = Video::factory()->create();

    Event::fake();

    $filePath1 = base_path('tests/Files/Subtitles/example_30_sec_eng.srt');
    $filePath2 = base_path('tests/Files/Subtitles/example_30_sec_rus.srt');

    $file1 = new UploadedFile($filePath1, 'eng.srt', null, null, true);
    $file2 = new UploadedFile($filePath2, 'rus.srt', null, null, true);

    $response = $this->post('/api/v1/videos/'.$video->id.'/upload-subs', ['files' => [$file1, $file2]]);

    expect($response->status())->toBe(200);

    $response_data = $response->getData();

    $video = Video::find($video->id);

    expect($response_data)->toBeObject()
        ->id->toBe($video->id);

    expect($video)
        ->is_subs_cut->toBeTrue()
        ->subs->toBeArray()
        ->clip_intervals->toBeArray();
});

it('load movie & thumb to video -> gets clips via http', function () {
    $video = Video::factory()->create();

    Event::fake();

    $filePath1 = base_path('tests/Files/Subtitles/example_30_sec_eng.srt');
    $filePath2 = base_path('tests/Files/Subtitles/example_30_sec_rus.srt');

    $file1 = new UploadedFile($filePath1, 'eng.srt', null, null, true);
    $file2 = new UploadedFile($filePath2, 'rus.srt', null, null, true);

    $this->post('/api/v1/videos/'.$video->id.'/upload-subs', ['files' => [$file1, $file2]]);
    // ^^^^^^^^^^^^^ ARRANGEMENT ^^^^^^^^^^^^^^^^^^

    $thumb = new UploadedFile(base_path('tests/Files/Images/Example.png'), 'thumb.png', null, null, true);
    $video_file = new UploadedFile(base_path('tests/Files/temp/test_video.mp4'), 'movie.mp4', null, null, true);

    $response = $this->post('/api/v1/videos/'.$video->id.'/upload-video', [
        'video' => $video_file,
        'thumb' => $thumb,
    ]);

    expect($response->status())->toBe(200);

    $video = Video::find($video->id);

    expect($video)
        ->video_processed->toBeTrue();

    $clips = Clip::where('video_id', $video->id)->get()->toArray();

    expect($clips)->toBeArray()->toHaveLength(4);

    $clips_response = $this->get('/api/v1/videos/'.$video->id.'/clips');

    expect($clips_response->status())->toBe(200);
    $clips_data = $clips_response->getData();

    expect($clips_data)->toBeObject()->data->toBeArray()->data->toHaveLength(4);

});
