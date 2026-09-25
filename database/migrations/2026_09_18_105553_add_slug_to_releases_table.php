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
        Schema::table('releases', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('release_code');
        });

        $used = [];
        $releases = DB::table('releases')->orderBy('id')->get();

        foreach ($releases as $release) {
            $base = Str::slug($release->title ?: 'release-' . $release->id);
            if ($base === '') {
                $base = 'release-' . $release->id;
            }

            $slug = $base;
            $i = 2;
            while (isset($used[$slug])) {
                $slug = "{$base}-{$i}";
                $i++;
            }

            $used[$slug] = true;
            DB::table('releases')
                ->where('id', $release->id)
                ->update(['slug' => $slug]);
        }

        Schema::table('releases', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};