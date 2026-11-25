<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number_id');
            $table->string('business_phone')->nullable();
            $table->string('customer_phone', 20);
            $table->string('customer_name')->nullable();
            $table->unsignedBigInteger('assigned_adviser_id')->nullable();
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
