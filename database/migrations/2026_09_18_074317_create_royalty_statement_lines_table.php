<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_statement_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('statement_id')
                ->constrained('royalty_statements')
                ->cascadeOnDelete();

            $table->foreignId('revenue_entry_id')->nullable()->constrained('revenue_entries')->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->foreignId('release_id')->nullable()->constrained('releases')->nullOnDelete();
            $table->foreignId('track_id')->nullable()->constrained('tracks')->nullOnDelete();

            $table->string('source', 40)->index();
            $table->string('description')->nullable();

            $table->decimal('revenue_amount', 14, 2);
            $table->decimal('royalty_rate', 5, 2);
            $table->decimal('royalty_amount', 14, 2);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('royalty_statement_lines');
    }
};