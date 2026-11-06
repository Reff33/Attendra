<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\EventSession;
use App\Models\Participant;
use Database\Seeders\AttendraDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCheckInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AttendraDemoSeeder::class);
    }

    public function test_student_can_view_public_checkin_page(): void
    {
        $session = EventSession::firstOrFail();

        $response = $this->get(route('public.checkin', $session->access_token));
        $response->assertStatus(200);
        $response->assertSee($session->title);
    }

    public function test_roster_search_returns_participants_for_session_activity(): void
    {
        $session = EventSession::firstOrFail();
        $participant = Participant::where('activity_id', $session->activity_id)->firstOrFail();

        $response = $this->get(route('public.roster-search', $session->access_token).'?q='.urlencode($participant->matric_no));

        $response->assertStatus(200);
        $response->assertJsonPath('participants.0.matric_no', $participant->matric_no);
    }

    public function test_student_successful_checkin_and_duplicate_prevention(): void
    {
        $session = EventSession::where('status', 'open')->firstOrFail();

        // Keep the session window around "now" so server-time validation passes.
        $session->update([
            'starts_at' => now()->subMinutes(10),
            'ends_at' => now()->addHour(),
        ]);

        // Pick a participant who has not checked in yet (the seeder pre-records some).
        $participant = Participant::where('activity_id', $session->activity_id)
            ->whereNotIn('matric_no', AttendanceRecord::where('event_session_id', $session->id)->pluck('matric_no'))
            ->firstOrFail();

        $payload = [
            'matric_no' => $participant->matric_no,
            'ic_last_4' => $participant->ic_last_4,
            'latitude' => $session->latitude,
            'longitude' => $session->longitude,
        ];

        // 1. First check-in creates exactly one record with a pass token.
        $this->post(route('public.checkin.submit', $session->access_token), $payload)
            ->assertRedirect();

        $record = AttendanceRecord::where('event_session_id', $session->id)
            ->where('matric_no', $participant->matric_no)
            ->first();

        $this->assertNotNull($record, 'Check-in did not create an attendance record.');
        $this->assertSame($participant->id, $record->participant_id);
        $this->assertNotEmpty($record->token_hash);
        $this->assertNotNull($record->checked_in_at);

        // 2. Duplicate check-in redirects to the existing pass, never a second record.
        $this->post(route('public.checkin.submit', $session->access_token), $payload)
            ->assertRedirect(route('public.pass', [
                'token' => $session->access_token,
                'hash' => $record->token_hash,
            ]));

        $this->assertSame(1, AttendanceRecord::where('event_session_id', $session->id)
            ->where('matric_no', $participant->matric_no)
            ->count());
    }

    public function test_checkin_rejected_when_session_window_has_passed(): void
    {
        $session = EventSession::where('status', 'open')->firstOrFail();
        $session->update([
            'starts_at' => now()->subHours(3),
            'ends_at' => now()->subHour(),
        ]);

        $participant = Participant::where('activity_id', $session->activity_id)
            ->whereNotIn('matric_no', AttendanceRecord::where('event_session_id', $session->id)->pluck('matric_no'))
            ->firstOrFail();

        $this->post(route('public.checkin.submit', $session->access_token), [
            'matric_no' => $participant->matric_no,
            'ic_last_4' => $participant->ic_last_4,
            'latitude' => $session->latitude,
            'longitude' => $session->longitude,
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('attendance_records', [
            'event_session_id' => $session->id,
            'matric_no' => $participant->matric_no,
        ]);
    }
}
