<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_code')->unique();

            // Keep contracts tied to artists. No cascade, no nullOnDelete.
            // Artists use soft deletes, so the FK constraint never fires on soft delete.
            $table->foreignId('artist_id')->constrained('artists');

            $table->string('title');
            $table->string('type', 40)->index();
            $table->string('status', 30)->default('draft')->index();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('signed_date')->nullable();

            $table->decimal('advance_amount', 14, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->decimal('royalty_rate', 5, 2)->nullable();

            $table->text('terms')->nullable();
            $table->string('document_path')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};