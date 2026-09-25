<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('artist_code');
        });

        // Backfill existing rows with unique slugs.
        $used = [];
        $artists = DB::table('artists')->orderBy('id')->get();

        foreach ($artists as $artist) {
            $base = Str::slug($artist->name ?: 'artist-' . $artist->id);
            if ($base === '') {
                $base = 'artist-' . $artist->id;
            }

            $slug = $base;
            $i = 2;
            while (isset($used[$slug])) {
                $slug = "{$base}-{$i}";
                $i++;
            }

            $used[$slug] = true;
            DB::table('artists')
                ->where('id', $artist->id)
                ->update(['slug' => $slug]);
        }

        // Now enforce uniqueness and non-nullability.
        Schema::table('artists', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};