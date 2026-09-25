<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class StaffConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(StaffMessage::class, 'conversation_id');
    }

    /**
     * Find or create a conversation between two users.
     * Normalizes the pair so user_one_id < user_two_id.
     */
    public static function findOrCreateBetween(int $a, int $b): self
    {
        if ($a === $b) {
            throw new \InvalidArgumentException('Cannot start a conversation with yourself.');
        }

        [$one, $two] = $a < $b ? [$a, $b] : [$b, $a];

        return DB::transaction(function () use ($one, $two) {
            $existing = static::where('user_one_id', $one)
                ->where('user_two_id', $two)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            return static::create([
                'user_one_id' => $one,
                'user_two_id' => $two,
            ]);
        });
    }

    /**
     * Returns the other participant's user id, given the current user's id.
     */
    public function otherUserId(int $currentUserId): int
    {
        return $this->user_one_id === $currentUserId
            ? $this->user_two_id
            : $this->user_one_id;
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_one_id', $userId)
                     ->orWhere('user_two_id', $userId);
    }
}