<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\EventSession;
use App\Models\User;
use Database\Seeders\AttendraDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $owner;
    private User $outsider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AttendraDemoSeeder::class);

        $this->admin = User::where('role', 'admin')->firstOrFail();
        $staff = User::where('role', 'staff')->get();
        $this->owner = $staff[0];
        $this->outsider = $staff[1];
    }

    private function sessionOwnedBy(User $user): EventSession
    {
        $activity = Activity::where('user_id', $user->id)->firstOrFail();

        return $activity->sessions()->first()
            ?? EventSession::create([
                'activity_id' => $activity->id,
                'title' => 'Fixture Session',
                'venue' => 'Dewan Al-Jazari',
                'access_token' => 'fixture-'.$user->id,
                'status' => 'open',
                'verification_mode' => 'none',
            ]);
    }

    public function test_staff_cannot_open_admin_console(): void
    {
        $this->actingAs($this->owner)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->get(route('admin.staff.index'))
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->post(route('admin.staff.store'), [
                'name' => 'Injected Admin',
                'email' => 'injected@utem.edu.my',
                'staff_id' => 'STF-XXX-999',
                'faculty' => 'FTMK',
                'password' => 'password123',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'injected@utem.edu.my']);
    }

    public function test_admin_can_open_admin_console(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.staff.index'))
            ->assertOk();
    }

    public function test_staff_cannot_toggle_or_export_another_staff_session(): void
    {
        $foreign = $this->sessionOwnedBy($this->outsider);

        $this->actingAs($this->owner)
            ->post(route('sessions.toggle', $foreign))
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->get(route('sessions.broadcast', $foreign))
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->get(route('sessions.export', $foreign))
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->get(route('sessions.live-stats', $foreign))
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->post(route('sessions.duplicate', $foreign))
            ->assertForbidden();
    }

    public function test_staff_cannot_create_session_under_another_staff_activity(): void
    {
        $foreignActivity = Activity::where('user_id', $this->outsider->id)->firstOrFail();

        $this->actingAs($this->owner)
            ->post(route('sessions.store'), [
                'activity_id' => $foreignActivity->id,
                'title' => 'Hijacked Session',
                'venue' => 'Dewan Al-Jazari',
                'verification_mode' => 'none',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('event_sessions', ['title' => 'Hijacked Session']);
    }

    public function test_owner_and_admin_can_manage_their_session(): void
    {
        $own = $this->sessionOwnedBy($this->owner);

        $this->actingAs($this->owner)
            ->get(route('sessions.broadcast', $own))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('sessions.broadcast', $own))
            ->assertOk();
    }

    public function test_staff_cannot_walk_in_or_scan_another_staff_session(): void
    {
        $foreign = $this->sessionOwnedBy($this->outsider);
        $foreign->update(['status' => 'open']);

        $this->actingAs($this->owner)
            ->post(route('quick-ops.walk-in.store'), [
                'session_id' => $foreign->id,
                'student_name' => 'Injected Student',
                'matric_no' => 'B099999999',
                'override_reason' => 'Unauthorized test',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('attendance_records', ['matric_no' => 'B099999999']);

        $this->actingAs($this->owner)
            ->postJson(route('quick-ops.scanner.scan'), [
                'session_id' => $foreign->id,
                'qr_payload' => 'B032110045',
            ])
            ->assertForbidden();
    }

    public function test_public_roster_search_is_rate_limited(): void
    {
        $session = EventSession::firstOrFail();
        $url = route('public.roster-search', $session->access_token).'?q=MU';

        // 20 requests per minute are allowed, the 21st is throttled.
        for ($i = 0; $i < 20; $i++) {
            $this->get($url)->assertOk();
        }

        $this->get($url)->assertStatus(429);
    }
}
