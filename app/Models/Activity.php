<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Activity extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'faculty',
        'description',
        'banner_path',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rosters(): HasMany
    {
        return $this->hasMany(Roster::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class);
    }

    public function activeRoster(): HasOne
    {
        return $this->hasOne(Roster::class)->latestOfMany();
    }
}
