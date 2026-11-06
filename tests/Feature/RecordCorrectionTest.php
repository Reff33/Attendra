<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\EventSession;
use App\Models\User;
use Database\Seeders\AttendraDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $outsider;
    private EventSession $session;
    private AttendanceRecord $record;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AttendraDemoSeeder::class);

        $staff = User::where('role', 'staff')->get();
        $this->owner = $staff[0];
        $this->outsider = $staff[1];

        $this->session = EventSession::where('access_token', 'amanat-ftmk-2026')->firstOrFail();
        $this->record = AttendanceRecord::where('event_session_id', $this->session->id)
            ->where('matric_no', 'B032110045')
            ->firstOrFail();
    }

    public function test_owner_can_correct_status_with_reason_and_it_is_audited(): void
    {
        $this->actingAs($this->owner)
            ->post(route('sessions.records.correct', [$this->session, $this->record]), [
                'status' => 'excused',
                'override_reason' => 'Medical leave submitted to faculty',
            ])
            ->assertRedirect();

        $this->assertSame('excused', $this->record->fresh()->status);
        $this->assertSame('Medical leave submitted to faculty', $this->record->fresh()->override_reason);
        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'RECORD_CORRECTION',
            'target_entity' => 'AttendanceRecord',
            'target_id' => (string) $this->record->id,
        ]);
    }

    public function test_correction_requires_a_reason(): void
    {
        $this->actingAs($this->owner)
            ->post(route('sessions.records.correct', [$this->session, $this->record]), [
                'status' => 'excused',
            ])
            ->assertSessionHasErrors('override_reason');

        $this->assertSame('present', $this->record->fresh()->status);
    }

    public function test_noop_correction_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->post(route('sessions.records.correct', [$this->session, $this->record]), [
                'status' => 'present',
                'override_reason' => 'Trying to set the same status',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, AuditLog::where('action_type', 'RECORD_CORRECTION')->count());
    }

    public function test_other_staff_cannot_correct_record(): void
    {
        $this->actingAs($this->outsider)
            ->post(route('sessions.records.correct', [$this->session, $this->record]), [
                'status' => 'excused',
                'override_reason' => 'Attempted cross-staff override',
            ])
            ->assertForbidden();

        $this->assertSame('present', $this->record->fresh()->status);
    }

    public function test_admin_can_correct_any_record(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('sessions.records.correct', [$this->session, $this->record]), [
                'status' => 'late',
                'override_reason' => 'Admin oversight correction',
            ])
            ->assertRedirect();

        $this->assertSame('late', $this->record->fresh()->status);
    }

    public function test_record_must_belong_to_the_session_in_the_url(): void
    {
        $otherSession = EventSession::where('access_token', 'ai-data-2026')->firstOrFail();

        $this->actingAs($this->owner)
            ->post(route('sessions.records.correct', [$otherSession, $this->record]), [
                'status' => 'excused',
                'override_reason' => 'Wrong session binding attempt',
            ])
            ->assertNotFound();

        $this->assertSame('present', $this->record->fresh()->status);
    }

    public function test_owner_can_delete_record_so_student_can_check_in_again(): void
    {
        $matric = $this->record->matric_no;

        // Seeded window is wall-clock based; widen it so the retry is inside the window.
        $this->session->update([
            'status' => 'open',
            'starts_at' => now()->subMinutes(5),
            'ends_at' => now()->addHour(),
        ]);

        $this->actingAs($this->owner)
            ->delete(route('sessions.records.destroy', [$this->session, $this->record]), [
                'override_reason' => 'Duplicate created by failed QR retry',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('attendance_records', ['id' => $this->record->id]);
        $this->assertDatabaseHas('audit_logs', ['action_type' => 'RECORD_DELETE']);

        // The unique constraint is free again.
        $this->post(route('public.checkin.submit', $this->session->access_token), [
            'matric_no' => $matric,
            'ic_last_4' => '4312',
            'latitude' => $this->session->latitude,
            'longitude' => $this->session->longitude,
        ])->assertRedirect();

        $this->assertSame(1, AttendanceRecord::where('event_session_id', $this->session->id)
            ->where('matric_no', $matric)->count());
    }

    public function test_matrix_page_renders_correction_modal(): void
    {
        $this->actingAs($this->owner)
            ->get(route('matrix.index', ['activity_id' => $this->session->activity_id]))
            ->assertOk()
            ->assertSee('matrixCorrection()', false)
            ->assertSee('Correct Attendance Record');
    }

    public function test_expired_open_session_is_auto_closed(): void
    {
        $this->session->update(['status' => 'open', 'ends_at' => now()->subMinute()]);

        $this->artisan('sessions:auto-close')->assertExitCode(0);

        $this->assertSame('closed', $this->session->fresh()->status);
    }

    public function test_live_session_is_not_auto_closed(): void
    {
        $this->session->update(['status' => 'open', 'ends_at' => now()->addHour()]);

        $this->artisan('sessions:auto-close')->assertExitCode(0);

        $this->assertSame('open', $this->session->fresh()->status);
    }
}
