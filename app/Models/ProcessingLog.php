<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessingLog extends Model
{
    protected $fillable = [
        'type',
        'logs',
        'instance_id'
    ];

    protected $casts = [
        'logs' => 'array',
    ];

}
