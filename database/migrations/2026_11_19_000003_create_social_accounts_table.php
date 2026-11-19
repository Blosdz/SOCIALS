<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adviser_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30);
            $table->string('provider_account_id')->nullable();
            $table->string('display_name')->nullable();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->timestamps();

            $table->unique(['adviser_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
