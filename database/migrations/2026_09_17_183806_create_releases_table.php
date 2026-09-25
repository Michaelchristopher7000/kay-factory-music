<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->string('release_code')->unique();

            // Non-nullable artist FK, no cascade — same policy as Contracts/Tracks.
            $table->foreignId('artist_id')->constrained('artists');

            $table->string('title')->index();
            $table->string('type', 30)->index();
            $table->string('status', 30)->default('draft')->index();

            $table->date('release_date')->nullable();
            $table->date('pre_save_date')->nullable();

            $table->string('upc', 14)->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('cover_art_path')->nullable();
            $table->text('label_copy')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};