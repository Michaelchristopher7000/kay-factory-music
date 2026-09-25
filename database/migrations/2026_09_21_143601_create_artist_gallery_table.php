<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artist_gallery', function (Blueprint $table) {
            $table->id();

            $table->foreignId('artist_id')
                ->constrained('artists')
                ->cascadeOnDelete();

            $table->string('image_path');
            $table->string('caption')->nullable();
            $table->string('alt_text')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);

            // draft | published
            $table->string('status')->default('published');

            $table->timestamps();

            $table->index(['artist_id', 'status']);
            $table->index(['artist_id', 'is_featured', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artist_gallery');
    }
};