<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialPublication extends Model
{
    protected $fillable = [
        'adviser_id',
        'marketing_content_id',
        'provider',
        'status',
        'external_id',
        'message',
        'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }
}
