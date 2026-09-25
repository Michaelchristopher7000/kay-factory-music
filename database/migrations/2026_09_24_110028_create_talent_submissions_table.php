<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_submissions', function (Blueprint $table) {
            $table->id();

            $table->string('reference_number')->unique();

            $table->string('full_name');
            $table->string('email');
            $table->string('phone');
            $table->string('location')->nullable();

            $table->string('talent_category');

            $table->text('bio')->nullable();
            $table->text('message')->nullable();

            $table->jsonb('social_links')->nullable();

            $table->string('audio_path')->nullable();
            $table->string('video_path')->nullable();
            $table->string('image_path')->nullable();

            $table->string('status')->default('pending');

            $table->text('manager_notes')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index('email');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_submissions');
    }
};