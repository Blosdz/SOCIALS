<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voice_calls', function (Blueprint $table): void {
            $table->string('adviser_phone')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('voice_calls', function (Blueprint $table): void {
            $table->string('adviser_phone')->nullable(false)->change();
        });
    }
};
