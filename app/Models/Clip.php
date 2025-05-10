<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clip extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'video_id',
        'video_path',
        'thumb_path',
        'subs',
    ];

    protected $casts = [
        'subs' => 'array',
    ];

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
