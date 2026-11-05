<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\AttendraDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionGeofenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AttendraDemoSeeder::class);
    }

    public function test_staff_can_create_session_with_map_coordinates_and_geofence(): void
    {
        $staff = User::where('role', 'staff')->firstOrFail();
        $activity = Activity::where('user_id', $staff->id)->firstOrFail();

        $response = $this->actingAs($staff)->post(route('sessions.store'), [
            'activity_id' => $activity->id,
            'title' => 'Seminar Keselamatan Siber FTMK',
            'venue' => 'Dewan Al-Jazari, FTMK',
            'verification_mode' => 'ic_and_gps',
            'latitude' => 2.313889,
            'longitude' => 102.319444,
            'geofence_radius_meters' => 150,
            'starts_at' => now()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect(route('sessions.index'));
        $this->assertDatabaseHas('event_sessions', [
            'title' => 'Seminar Keselamatan Siber FTMK',
            'latitude' => 2.313889,
            'longitude' => 102.319444,
            'geofence_radius_meters' => 150,
        ]);
    }

    public function test_student_outside_geofence_is_rejected_by_server_haversine(): void
    {
        $session = EventSession::where('status', 'open')->firstOrFail();
        $session->update([
            'verification_mode' => 'gps',
            'latitude' => 2.313889, // Dewan Al-Jazari
            'longitude' => 102.319444,
            'geofence_radius_meters' => 100,
        ]);

        // Pick participant not yet checked in
        $participant = Participant::whereHas('roster', function ($q) use ($session) {
            $q->where('activity_id', $session->activity_id);
        })->whereNotIn('matric_no', ['B032110045', 'B032110112', 'B032110290'])
          ->firstOrFail();

        // Coordinate ~2.5km away in Ayer Keroh
        $response = $this->post(route('public.checkin.submit', $session->access_token), [
            'matric_no' => $participant->matric_no,
            'latitude' => 2.270000,
            'longitude' => 102.280000,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('attendance_records', [
            'event_session_id' => $session->id,
            'matric_no' => $participant->matric_no,
        ]);
    }
}
