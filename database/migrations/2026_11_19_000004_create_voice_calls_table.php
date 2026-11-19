<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adviser_id')->constrained()->cascadeOnDelete();
            $table->string('client_phone');
            $table->string('adviser_phone');
            $table->string('twilio_call_sid')->nullable()->unique();
            $table->string('status')->default('queued');
            $table->string('recording_sid')->nullable()->unique();
            $table->text('recording_url')->nullable();
            $table->string('transcription_sid')->nullable();
            $table->string('transcription_status')->nullable();
            $table->longText('transcript')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_calls');
    }
};
