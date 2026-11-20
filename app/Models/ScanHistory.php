<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanHistory extends Model
{
    use HasFactory;

    protected $table = 'scan_histories';

    protected $fillable = [
        'query',
        'normalized_query',
        'query_type',
        'verdict',
        'matched_ioc_id',
        'matched_ioc_value',
        'matched_type',
        'matched_tags',
        'risk_score',
        'risk_explanation',
        'client_ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'matched_tags' => 'array',
            'risk_score' => 'integer',
        ];
    }

    public function matchedThreatIoc(): BelongsTo
    {
        return $this->belongsTo(ThreatIoc::class, 'matched_ioc_id');
    }

    public function getVerdictColorAttribute(): string
    {
        return match ($this->verdict) {
            'malicious' => 'text-red-700 bg-red-50 border-red-200',
            'watchlist' => 'text-amber-700 bg-amber-50 border-amber-200',
            'whitelist' => 'text-blue-700 bg-blue-50 border-blue-200',
            default => 'text-emerald-700 bg-emerald-50 border-emerald-200',
        };
    }

    public function getVerdictLabelAttribute(): string
    {
        return match ($this->verdict) {
            'malicious' => 'Terindikasi Berbahaya',
            'watchlist' => 'Watchlist Internal',
            'whitelist' => 'Whitelist Terpercaya',
            default => 'Aman / Bersih',
        };
    }
}
