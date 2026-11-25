<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappNumber extends Model
{
    protected $primaryKey = 'phone_number_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['phone_number_id', 'display_phone', 'adviser_id'];

    protected function casts(): array
    {
        return ['adviser_id' => 'integer'];
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(Adviser::class);
    }
}
