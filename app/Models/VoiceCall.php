<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceCall extends Model
{
    protected $fillable = [
        'adviser_id',
        'client_phone',
        'adviser_phone',
        'twilio_call_sid',
        'status',
        'recording_sid',
        'recording_url',
        'transcription_sid',
        'transcription_status',
        'transcript',
    ];

    protected function casts(): array
    {
        return [
            'recording_url' => 'encrypted',
            'transcript' => 'encrypted',
        ];
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(Adviser::class);
    }
}
