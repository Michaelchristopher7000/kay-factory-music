<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtistVideo extends Model
{
    use HasFactory;

    public const SOURCE_YOUTUBE = 'youtube';
    public const SOURCE_UPLOAD = 'upload';

    public const TYPE_MUSIC_VIDEO = 'music_video';
    public const TYPE_VISUALIZER = 'visualizer';
    public const TYPE_LIVE = 'live';
    public const TYPE_INTERVIEW = 'interview';
    public const TYPE_BEHIND_THE_SCENES = 'behind_the_scenes';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'artist_id',
        'title',
        'description',
        'type',
        'source',
        'youtube_id',
        'video_path',
        'thumbnail_path',
        'published_at',
        'is_featured',
        'is_home_featured',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_home_featured' => 'boolean',
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    /**
     * Scope: only published videos.
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope: order for public display (featured → sort_order → published_at).
     */
    public function scopeOrdered($query)
    {
        return $query
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('published_at');
    }

    /**
     * The single video shown on the home page.
     * Returns null if none is featured or the featured one isn't published.
     */
    public static function homeFeatured(): ?self
    {
        return static::query()
            ->where('is_home_featured', true)
            ->published()
            ->with('artist:id,slug,name,avatar,genre')
            ->latest()
            ->first();
    }

    /**
     * Extract a YouTube video ID from any common URL format.
     * Returns null if the URL cannot be parsed.
     */
    public static function extractYoutubeId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $patterns = [
            '/(?:youtube\.com\/watch\?(?:.*&)?v=)([A-Za-z0-9_-]{11})/',
            '/(?:youtu\.be\/)([A-Za-z0-9_-]{11})/',
            '/(?:youtube\.com\/embed\/)([A-Za-z0-9_-]{11})/',
            '/(?:youtube\.com\/shorts\/)([A-Za-z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public static function parseYoutubeUrl(string $url): ?string
    {
        return static::extractYoutubeId($url);
    }

    public function getYoutubeThumbnailUrlAttribute(): ?string
    {
        if ($this->source !== self::SOURCE_YOUTUBE || ! $this->youtube_id) {
            return null;
        }

        return "https://img.youtube.com/vi/{$this->youtube_id}/maxresdefault.jpg";
    }
}