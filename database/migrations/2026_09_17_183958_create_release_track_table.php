<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('release_track', function (Blueprint $table) {
            $table->id();

            $table->foreignId('release_id')->constrained('releases')->cascadeOnDelete();
            $table->foreignId('track_id')->constrained('tracks')->cascadeOnDelete();

            $table->unsignedSmallInteger('position')->default(1);

            $table->timestamps();

            $table->unique(['release_id', 'track_id']);
            $table->index(['release_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('release_track');
    }
};