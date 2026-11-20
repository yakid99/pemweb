<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Watchlist extends Model
{
    use HasFactory;

    protected $table = 'watchlists';

    protected $fillable = [
        'type',
        'value',
        'defanged_value',
        'host_domain',
        'list_type',
        'category',
        'severity',
        'notes',
        'created_by',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWatchlists(Builder $query): Builder
    {
        return $query->where('list_type', 'watchlist');
    }

    public function scopeWhitelists(Builder $query): Builder
    {
        return $query->where('list_type', 'whitelist');
    }

    public function getListBadgeAttribute(): string
    {
        return $this->list_type === 'whitelist'
            ? 'bg-blue-50 text-blue-700 border-blue-200'
            : 'bg-amber-50 text-amber-700 border-amber-200';
    }

    public function getListLabelAttribute(): string
    {
        return $this->list_type === 'whitelist' ? 'Whitelist (Aman)' : 'Watchlist (Dipantau)';
    }
}
