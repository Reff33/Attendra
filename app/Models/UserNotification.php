<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'priority',
        'url',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create an in-app notification for one user.
     *
     * Named `notify` because `push` is already an Eloquent Model method.
     */
    public static function notify(
        int $userId,
        string $title,
        ?string $message = null,
        string $type = 'system',
        ?string $url = null,
        string $priority = 'normal'
    ): self {
        return self::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'priority' => $priority,
            'url' => $url,
        ]);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
