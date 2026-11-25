<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappConversation extends Model
{
    protected $fillable = [
        'phone_number_id', 'business_phone', 'customer_phone', 'customer_name',
        'assigned_adviser_id', 'last_inbound_at',
    ];

    protected function casts(): array
    {
        return ['assigned_adviser_id' => 'integer', 'last_inbound_at' => 'datetime'];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class, 'conversation_id');
    }

    public function assignedAdviser(): BelongsTo
    {
        return $this->belongsTo(Adviser::class, 'assigned_adviser_id');
    }
}
