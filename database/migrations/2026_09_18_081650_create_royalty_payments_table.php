<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_code')->unique();

            $table->foreignId('statement_id')->constrained('royalty_statements');
            $table->foreignId('artist_id')->constrained('artists');

            $table->decimal('amount', 14, 2);
            $table->string('currency', 3);

            $table->date('paid_at')->index();
            $table->string('method', 40)->index();

            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('royalty_payments');
    }
};