<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    protected $fillable = ['adviser_id', 'name', 'dni', 'email', 'phone', 'extension'];

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(Adviser::class);
    }
}
