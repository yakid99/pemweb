<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThreatIoc extends Model
{
    use HasFactory;

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

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeByType(Builder $query, ?string $type): Builder
    {
        if (!$type || $type === 'all' || $type === 'semua') {
            return $query;
        }

        if ($type === 'hash' || $type === 'file_hash') {
            return $query->whereIn('type', ['sha256', 'md5']);
        }

        return $query->where('type', $type);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = trim($term);
        $wildcard = '%' . $term . '%';

        return $query->where(function (Builder $q) use ($wildcard, $term) {
            $q->where('value', 'like', $wildcard)
                ->orWhere('defanged_value', 'like', $wildcard)
                ->orWhere('host_domain', 'like', $wildcard)
                ->orWhere('source_user', 'like', $wildcard)
                ->orWhere('category', 'like', $wildcard)
                ->orWhereRaw('CAST(tags AS CHAR) LIKE ?', [$wildcard]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getFormattedTagsAttribute(): array
    {
        return is_array($this->tags) ? $this->tags : [];
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'text-red-700 bg-red-50 border-red-200',
            'warning' => 'text-amber-700 bg-amber-50 border-amber-200',
            default => 'text-slate-700 bg-slate-100 border-slate-200',
        };
    }

    public function getSeverityBadgeAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'CRITICAL',
            'warning' => 'WARNING',
            default => 'INFO',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'url' => 'URL',
            'domain' => 'Domain',
            'ip' => 'IP Address',
            'sha256' => 'SHA-256',
            'md5' => 'MD5',
            default => strtoupper($this->type),
        };
    }
}
