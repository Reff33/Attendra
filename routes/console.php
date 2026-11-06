<?php

use App\Models\EventSession;
use App\Models\UserNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Server time (Asia/Kuala_Lumpur) governs attendance validity. Check-in already
// rejects expired windows; this keeps the stored status in step with the clock.
Artisan::command('sessions:auto-close', function () {
    $expired = EventSession::with('activity:id,user_id,title')
        ->where('status', 'open')
        ->whereNotNull('ends_at')
        ->where('ends_at', '<', now())
        ->get();

    foreach ($expired as $session) {
        $session->update(['status' => 'closed']);

        if ($ownerId = $session->activity?->user_id) {
            UserNotification::notify(
                $ownerId,
                'Session closed automatically',
                "\"{$session->title}\" reached its end time and is now closed for check-in.",
                'session',
                route('matrix.index', ['activity_id' => $session->activity_id]),
                'normal'
            );
        }
    }

    $this->info("Closed {$expired->count()} expired session(s).");
})->purpose('Close sessions whose end time has passed');

Schedule::command('sessions:auto-close')->everyMinute();
