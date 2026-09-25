<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artist_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('artist_id')
                ->constrained('artists')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('venue')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();

            $table->dateTime('event_date');
            $table->time('event_time')->nullable();

            $table->string('ticket_url')->nullable();
            $table->string('event_url')->nullable();
            $table->string('image_path')->nullable();

            // draft | published
            $table->string('status')->default('draft');

            $table->timestamps();

            $table->index(['artist_id', 'status']);
            $table->index(['artist_id', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artist_events');
    }
};