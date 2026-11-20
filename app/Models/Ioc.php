<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ioc extends Model
{
    protected $table = 'threat_iocs';

    protected $fillable = [
        'source_key',
        'type',
        'value',
        'defanged_value',
        'host_domain',
        'source_user',
        'source_tweet',
        'tags',
        'category',
        'severity',
        'status',
        'source_date',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'source_date' => 'datetime',
        ];
    }

    public function getIsFalsePositiveAttribute(): bool
    {
        return $this->status === 'false_positive';
    }

   public function getSpssStatusCodeAttribute(): int
    {
        return match ($this->status) {
            'active' => 1,
            'inactive' => 0,
            'false_positive' => 2,
            default => -1,
        };
    }
}
