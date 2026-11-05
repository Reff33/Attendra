<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EventSession extends Model
{
    protected $fillable = [
        'activity_id',
        'title',
        'venue',
        'access_token',
        'status',
        'verification_mode',
        'latitude',
        'longitude',
        'geofence_radius_meters',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'geofence_radius_meters' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            if (empty($session->access_token)) {
                $session->access_token = Str::random(12);
            }
        });
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
