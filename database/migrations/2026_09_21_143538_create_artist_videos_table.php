<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artist_videos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('artist_id')
                ->constrained('artists')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // music_video | visualizer | live | interview | behind_the_scenes
            $table->string('type')->default('music_video');

            // youtube | upload
            $table->string('source');

            // For YouTube videos
            $table->string('youtube_id')->nullable();

            // For uploaded videos
            $table->string('video_path')->nullable();

            // Thumbnail (works for both YouTube and uploaded)
            $table->string('thumbnail_path')->nullable();

            $table->timestamp('published_at')->nullable();

            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            // draft | published
            $table->string('status')->default('draft');

            $table->timestamps();

            $table->index(['artist_id', 'status']);
            $table->index(['artist_id', 'is_featured', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artist_videos');
    }
};