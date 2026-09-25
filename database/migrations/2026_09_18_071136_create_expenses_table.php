<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_code')->unique();

            $table->string('category', 40)->index();

            $table->foreignId('artist_id')->nullable()->constrained('artists')->nullOnDelete();
            $table->foreignId('release_id')->nullable()->constrained('releases')->nullOnDelete();
            $table->foreignId('track_id')->nullable()->constrained('tracks')->nullOnDelete();
            $table->foreignId('distribution_id')->nullable()->constrained('distributions')->nullOnDelete();

            $table->decimal('amount', 14, 2);
            $table->string('currency', 3);

            $table->date('incurred_at')->nullable()->index();
            $table->date('paid_at')->nullable();

            $table->string('reference', 100)->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};