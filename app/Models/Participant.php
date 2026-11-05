<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    protected $fillable = [
        'roster_id',
        'activity_id',
        'name',
        'matric_no',
        'ic_last_4',
        'program',
        'email',
    ];

    public function getStudentNameAttribute(): string
    {
        return $this->name;
    }

    public function getProgramCodeAttribute(): ?string
    {
        return $this->program;
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function roster(): BelongsTo
    {
        return $this->belongsTo(Roster::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
