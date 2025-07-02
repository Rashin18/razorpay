<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    protected $table = 'webhook_events';
    
    protected $casts = [
        'payload' => 'array', // Changed from 'json' to 'array' for better compatibility
    ];
    
    protected $fillable = [
        'event_type',
        'entity_id',
        'payload',
        'status',
        'processing_errors'
    ];
    
    // Add this to prevent any mass assignment issues
    protected $guarded = [];
}