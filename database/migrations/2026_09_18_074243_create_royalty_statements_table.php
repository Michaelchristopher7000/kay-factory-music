<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_statements', function (Blueprint $table) {
            $table->id();
            $table->string('statement_code')->unique();

            $table->foreignId('artist_id')->constrained('artists');

            $table->date('period_start');
            $table->date('period_end')->index();

            $table->string('status', 30)->default('draft')->index();
            $table->string('currency', 3);

            $table->decimal('royalty_rate', 5, 2)->default(0);

            $table->decimal('total_revenue', 14, 2)->default(0);
            $table->decimal('total_royalty', 14, 2)->default(0);
            $table->decimal('total_paid', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);

            $table->date('issued_at')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('royalty_statements');
    }
};