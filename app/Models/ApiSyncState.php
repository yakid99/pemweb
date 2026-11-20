<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiSyncState extends Model
{
    protected $fillable = [
        'endpoint',
        'status',
        'pipeline_stale',
        'etag_today',
        'etag_week',
        'last_window_end',
        'last_success_at',
        'records_last_sync',
        'last_error',
        'status_payload',
    ];

    protected function casts(): array
    {
        return [
            'pipeline_stale' => 'boolean',
            'last_window_end' => 'datetime',
            'last_success_at' => 'datetime',
            'status_payload' => 'array',
        ];
    }
}
