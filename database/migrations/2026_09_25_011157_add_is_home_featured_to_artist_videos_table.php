<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artist_videos', function (Blueprint $table) {
            $table->boolean('is_home_featured')->default(false)->after('is_featured');
            $table->index(['is_home_featured', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('artist_videos', function (Blueprint $table) {
            $table->dropIndex(['is_home_featured', 'status']);
            $table->dropColumn('is_home_featured');
        });
    }
};