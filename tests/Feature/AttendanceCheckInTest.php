<?php

namespace Tests\Feature;

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

    public function test_student_successful_checkin_and_duplicate_prevention(): void
    {
        $session = EventSession::where('status', 'open')->firstOrFail();

        $participant = Participant::whereHas('roster', function ($q) use ($session) {
            $q->where('activity_id', $session->activity_id);
        })->firstOrFail();

        // 1. First check-in
        $response = $this->post(route('public.checkin.submit', $session->access_token), [
            'matric_no' => $participant->matric_no,
            'ic_last_4' => $participant->ic_last_4,
            'latitude' => 2.3138,
            'longitude' => 102.3195,
        ]);

        $response->assertRedirect();

        // 2. Verify record in database
        $this->assertDatabaseHas('attendance_records', [
            'event_session_id' => $session->id,
            'matric_no' => $participant->matric_no,
        ]);

        // 3. Duplicate check-in redirected to existing pass
        $duplicateResponse = $this->post(route('public.checkin.submit', $session->access_token), [
            'matric_no' => $participant->matric_no,
            'ic_last_4' => $participant->ic_last_4,
            'latitude' => 2.3138,
            'longitude' => 102.3195,
        ]);

        $duplicateResponse->assertRedirect();
    }
}
