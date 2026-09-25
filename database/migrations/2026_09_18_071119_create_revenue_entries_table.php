<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_code')->unique();

            $table->string('source', 40)->index();
            $table->string('platform', 40)->nullable();

            $table->foreignId('artist_id')->nullable()->constrained('artists')->nullOnDelete();
            $table->foreignId('release_id')->nullable()->constrained('releases')->nullOnDelete();
            $table->foreignId('track_id')->nullable()->constrained('tracks')->nullOnDelete();
            $table->foreignId('distribution_id')->nullable()->constrained('distributions')->nullOnDelete();

            $table->decimal('amount', 14, 2);
            $table->string('currency', 3);

            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable()->index();
            $table->date('received_at')->nullable();

            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_entries');
    }
};