<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class WebhookEvent extends Model
{
    protected $fillable = ['event_type', 'entity_id', 'payload'];

    protected $casts = [
        'payload' => 'array',
    ];
}
