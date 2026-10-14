<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace',
        'user_id',
        'access_token',
        'refresh_token',
        'token_type',
        'scope',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function willExpireSoon(int $minutes = 5): bool
    {
        return $this->expires_at->lessThanOrEqualTo(now()->addMinutes($minutes));
    }
}
