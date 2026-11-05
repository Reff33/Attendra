<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\EventSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAndStaffEnhancementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['session.driver' => 'database']);
        $this->seed(\Database\Seeders\AttendraDemoSeeder::class);
    }

    public function test_admin_can_toggle_staff_status_and_block_suspended_login(): void
    {
        $admin = User::where('role', 'admin')->first();
        $staff = User::where('role', 'staff')->first();

        // 1. Admin suspends staff
        $response = $this->actingAs($admin)->post(route('admin.staff.toggle', $staff->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('suspended', $staff->fresh()->status);

        // 2. Suspended staff tries to login (clear prior admin auth session in test)
        auth()->logout();

        $loginResponse = $this->post(route('login'), [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $loginResponse->assertSessionHasErrors('email');
        $this->assertGuest();

        // 3. Admin reactivates staff
        $reactivateResponse = $this->actingAs($admin)->post(route('admin.staff.toggle', $staff->id));
        $reactivateResponse->assertRedirect();
        $this->assertEquals('active', $staff->fresh()->status);
    }

    public function test_admin_can_reset_staff_password(): void
    {
        $admin = User::where('role', 'admin')->first();
        $staff = User::where('role', 'staff')->first();

        $response = $this->actingAs($admin)->post(route('admin.staff.reset-password', $staff->id), [
            'password' => 'newSecretKey2026',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('newSecretKey2026', $staff->fresh()->password));
    }

    public function test_staff_can_duplicate_session(): void
    {
        $staff = User::where('role', 'staff')->first();
        $session = EventSession::whereHas('activity', fn ($q) => $q->where('user_id', $staff->id))->first();

        $initialCount = EventSession::count();

        $response = $this->actingAs($staff)->post(route('sessions.duplicate', $session->id));
        $response->assertRedirect(route('sessions.index'));
        $response->assertSessionHas('success');

        $this->assertEquals($initialCount + 1, EventSession::count());
        $this->assertDatabaseHas('event_sessions', [
            'title' => $session->title . ' (Copy)',
            'venue' => $session->venue,
            'verification_mode' => $session->verification_mode,
        ]);
    }

    public function test_audit_trail_is_restricted_to_admin_only(): void
    {
        $staff = User::where('role', 'staff')->first();
        $admin = User::where('role', 'admin')->first();

        // 1. Regular staff is forbidden (403)
        $forbiddenResponse = $this->actingAs($staff)->get(route('admin.audit.index'));
        $forbiddenResponse->assertStatus(403);

        $forbiddenExport = $this->actingAs($staff)->get(route('admin.audit.export'));
        $forbiddenExport->assertStatus(403);

        // 2. Admin can view index and filter
        $adminResponse = $this->actingAs($admin)->get(route('admin.audit.index', ['search' => 'SESSION']));
        $adminResponse->assertStatus(200);

        // 3. Admin can export CSV
        $exportResponse = $this->actingAs($admin)->get(route('admin.audit.export'));
        $exportResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $exportResponse->headers->get('content-type'));
    }
}
