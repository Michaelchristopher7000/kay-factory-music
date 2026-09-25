<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->string('distribution_code')->unique();

            $table->foreignId('release_id')->constrained('releases');

            $table->string('platform', 40)->index();
            $table->string('distributor', 100)->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->string('territory', 40)->nullable();

            $table->date('scheduled_for')->nullable();
            $table->date('submitted_at')->nullable();
            $table->date('live_at')->nullable();
            $table->date('takedown_at')->nullable();

            $table->string('platform_release_id', 100)->nullable();
            $table->string('platform_url', 500)->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['release_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributions');
    }
};