<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->string('track_code')->unique();

            // Non-nullable artist FK, no cascade, no nullOnDelete.
            // Artists use soft deletes so the FK never fires on soft delete.
            $table->foreignId('artist_id')->constrained('artists');

            $table->string('title')->index();
            $table->string('isrc', 12)->nullable()->unique();

            $table->unsignedInteger('duration_seconds')->nullable();

            $table->string('genre')->nullable()->index();
            $table->string('language', 10)->nullable();
            $table->unsignedSmallInteger('bpm')->nullable();
            $table->string('key', 10)->nullable();

            $table->boolean('is_explicit')->default(false);

            $table->string('composer')->nullable();

            $table->jsonb('writers')->nullable();
            $table->jsonb('producers')->nullable();
            $table->jsonb('featured_artists')->nullable();

            $table->date('recorded_date')->nullable();

            $table->text('lyrics')->nullable();
            $table->string('audio_path')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
};