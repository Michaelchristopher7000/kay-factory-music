<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_devices', function (Blueprint $table) {
            // ISO 3166-1 alpha-2 country code (e.g. "NG", "US", "GB")
            // Nullable so legacy rows and unresolvable IPs are left intact.
            $table->string('country_code', 2)
                ->nullable()
                ->after('location');

            // Composite index — powers the "has this user ever logged in from
            // this country?" query on the login hot path.
            $table->index(['user_id', 'country_code'], 'login_devices_user_country_idx');
        });
    }

    public function down(): void
    {
        Schema::table('login_devices', function (Blueprint $table) {
            $table->dropIndex('login_devices_user_country_idx');
            $table->dropColumn('country_code');
        });
    }
};