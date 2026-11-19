<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingContent extends Model
{
    protected $fillable = ['title', 'description', 'images', 'purchase_url', 'is_active'];

    protected function casts(): array
    {
        return ['images' => 'array', 'is_active' => 'boolean'];
    }
}
