<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'event_session_id',
        'participant_id',
        'student_name',
        'matric_no',
        'verification_method',
        'status',
        'is_walk_in',
        'override_reason',
        'token_hash',
        'ip_address',
        'user_agent',
        'recorded_latitude',
        'recorded_longitude',
        'distance_meters',
        'checked_in_at',
    ];

    protected $casts = [
        'is_walk_in' => 'boolean',
        'recorded_latitude' => 'float',
        'recorded_longitude' => 'float',
        'distance_meters' => 'float',
        'checked_in_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(EventSession::class, 'event_session_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
