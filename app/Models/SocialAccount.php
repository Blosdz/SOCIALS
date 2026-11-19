<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    protected $fillable = [
        'adviser_id',
        'provider',
        'provider_account_id',
        'display_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'refresh_token_expires_at',
        'scopes',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'refresh_token_expires_at' => 'datetime',
            'scopes' => 'array',
        ];
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(Adviser::class);
    }
}
