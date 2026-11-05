<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action_type',
        'actor_name',
        'actor_staff_id',
        'target_entity',
        'target_id',
        'details',
        'ip_address',
        'sha256_hash',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $actionType, string $targetEntity, ?string $targetId, ?string $details = null, ?User $actor = null): self
    {
        $actor = $actor ?? auth()->user();
        $actorName = $actor ? $actor->name : 'System';
        $actorStaffId = $actor ? $actor->staff_id : 'SYS-AUTO';
        $ip = request()->ip() ?? '127.0.0.1';
        $timestamp = now()->toIso8601String();

        $hashInput = "{$actionType}|{$actorStaffId}|{$targetEntity}|{$targetId}|{$timestamp}|" . config('app.key');
        $sha256 = hash('sha256', $hashInput);

        return self::create([
            'user_id' => $actor?->id,
            'action_type' => $actionType,
            'actor_name' => $actorName,
            'actor_staff_id' => $actorStaffId,
            'target_entity' => $targetEntity,
            'target_id' => $targetId,
            'details' => $details,
            'ip_address' => $ip,
            'sha256_hash' => $sha256,
            'created_at' => now(),
        ]);
    }
}
