<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adviser_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketing_content_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30);
            $table->string('status', 40);
            $table->string('external_id')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_publications');
    }
};
