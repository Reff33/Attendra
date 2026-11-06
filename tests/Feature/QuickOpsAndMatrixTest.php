<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AttendanceRecord;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\Roster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickOpsAndMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AttendraDemoSeeder::class);
    }

    public function test_staff_camera_scan_endpoint_validates_and_records_attendance(): void
    {
        $staff = User::where('role', 'staff')->first();
        $session = EventSession::where('status', 'open')->first();

        // Find participant who hasn't checked in yet
        $checkedInMatrics = AttendanceRecord::where('event_session_id', $session->id)->pluck('matric_no')->toArray();
        $unattendingParticipant = Participant::where('activity_id', $session->activity_id)
            ->whereNotIn('matric_no', $checkedInMatrics)
            ->first();

        $this->assertNotNull($unattendingParticipant);

        // Scan participant card QR containing their matric number
        $response = $this->actingAs($staff)->postJson(route('quick-ops.scanner.scan'), [
            'session_id' => $session->id,
            'qr_payload' => $unattendingParticipant->matric_no,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'checked_in_success',
            'matric_no' => $unattendingParticipant->matric_no,
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'event_session_id' => $session->id,
            'matric_no' => $unattendingParticipant->matric_no,
            'verification_method' => 'qr_staff_scan',
        ]);
    }

    public function test_staff_can_export_cohort_attendance_matrix_csv(): void
    {
        $staff = User::where('role', 'staff')->first();
        $activity = Activity::where('user_id', $staff->id)->first();

        $response = $this->actingAs($staff)->get(route('matrix.export', [
            'activity_id' => $activity->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertTrue(str_contains($response->headers->get('content-disposition'), 'Attendra_Matrix_'));
    }

    public function test_restructured_staff_pages_render(): void
    {
        $staff = User::where('role', 'staff')->first();
        $activity = Activity::where('user_id', $staff->id)->firstOrFail();

        foreach ([
            route('activities.create'),
            route('activities.edit', $activity),
            route('sessions.create'),
            route('quick-ops.scanner'),
            route('quick-ops.walk-in'),
            route('notifications.index'),
        ] as $url) {
            $this->actingAs($staff)->get($url)->assertOk();
        }
    }
}
