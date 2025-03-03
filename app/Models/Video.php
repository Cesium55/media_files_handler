<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        "title",
        "description",
        "language",
        "video_path",
        "thumb_path",
        "is_subs_cut",
        "video_processed",
        "subs",
        "clip_intervals",
    ];

    protected $casts = [
        "is_subs_cut" => "boolean",
        "video_processed" => "boolean",
        "subs" => "array",
        "clip_intervals" => "array",
    ];

    public function clips()
    {
        return $this->hasMany(Clip::class);
    }
}
