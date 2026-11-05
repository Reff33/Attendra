<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Roster extends Model
{
    protected $fillable = [
        'activity_id',
        'filename',
        'dataset_label',
        'version',
        'total_records',
        'is_frozen',
    ];

    protected $casts = [
        'is_frozen' => 'boolean',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }
}
