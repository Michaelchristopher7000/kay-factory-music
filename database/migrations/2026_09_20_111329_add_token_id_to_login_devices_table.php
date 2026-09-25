<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_devices', function (Blueprint $table) {
            $table->unsignedBigInteger('token_id')->nullable()->after('device_hash');
            $table->index('token_id');
        });
    }

    public function down(): void
    {
        Schema::table('login_devices', function (Blueprint $table) {
            $table->dropIndex(['token_id']);
            $table->dropColumn('token_id');
        });
    }
};