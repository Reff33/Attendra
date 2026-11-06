<?php

namespace App\Policies;

use App\Models\EventSession;
use App\Models\User;

class EventSessionPolicy
{
    /**
     * Admin oversees everything; staff only their own activities (PRD §5.2 / Domain Rule 1).
     */
    public function manage(User $user, EventSession $session): bool
    {
        return $user->isAdmin() || $session->activity?->user_id === $user->id;
    }
}
