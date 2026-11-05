<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['session.driver' => 'database']);
        $this->seed(\Database\Seeders\AttendraDemoSeeder::class);
    }

    public function test_user_can_view_profile_page(): void
    {
        $user = User::where('role', 'staff')->first();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee($user->email);
    }

    public function test_user_can_update_profile_information(): void
    {
        $user = User::where('role', 'staff')->first();

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Faris Imran Updated',
            'email' => 'faris.updated@utem.edu.my',
            'phone' => '+6011-2345678',
            'faculty' => 'FTMK',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Faris Imran Updated',
            'email' => 'faris.updated@utem.edu.my',
            'phone' => '+6011-2345678',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'PROFILE_UPDATE',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_update_password(): void
    {
        $user = User::where('role', 'staff')->first();

        $response = $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));

        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'PASSWORD_CHANGE',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_update_password_with_incorrect_current_password(): void
    {
        $user = User::where('role', 'staff')->first();

        $response = $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'wrong-current-password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertFalse(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_user_can_revoke_other_device_sessions(): void
    {
        $user = User::where('role', 'staff')->first();

        // Seed 2 external sessions in sessions table
        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            ['id' => 'sess_device_1', 'user_id' => $user->id, 'ip_address' => '192.168.1.10', 'user_agent' => 'Mozilla/5.0 Chrome', 'payload' => 'dummy', 'last_activity' => time()],
            ['id' => 'sess_device_2', 'user_id' => $user->id, 'ip_address' => '192.168.1.11', 'user_agent' => 'Mozilla/5.0 iPhone', 'payload' => 'dummy', 'last_activity' => time()],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['test' => true])
            ->post(route('profile.sessions.revoke-others'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(0, \Illuminate\Support\Facades\DB::table('sessions')
            ->where('user_id', $user->id)
            ->whereIn('id', ['sess_device_1', 'sess_device_2'])
            ->count());

        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'REVOKE_ALL_OTHER_SESSIONS',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_revoke_single_device_session(): void
    {
        $user = User::where('role', 'staff')->first();

        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            'id' => 'sess_device_remote',
            'user_id' => $user->id,
            'ip_address' => '10.0.0.5',
            'user_agent' => 'Mozilla/5.0 Firefox',
            'payload' => 'dummy',
            'last_activity' => time(),
        ]);

        $response = $this->actingAs($user)
            ->delete(route('profile.sessions.revoke', 'sess_device_remote'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('sessions', [
            'id' => 'sess_device_remote',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'REVOKE_DEVICE_SESSION',
            'user_id' => $user->id,
        ]);
    }

    public function test_login_enforces_three_active_devices_limit(): void
    {
        $user = User::where('role', 'staff')->first();

        // Populate 3 active sessions for user
        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            ['id' => 'dev_1', 'user_id' => $user->id, 'ip_address' => '192.168.1.1', 'user_agent' => 'Chrome', 'payload' => 'x', 'last_activity' => time()],
            ['id' => 'dev_2', 'user_id' => $user->id, 'ip_address' => '192.168.1.2', 'user_agent' => 'Safari', 'payload' => 'x', 'last_activity' => time()],
            ['id' => 'dev_3', 'user_id' => $user->id, 'ip_address' => '192.168.1.3', 'user_agent' => 'Edge', 'payload' => 'x', 'last_activity' => time()],
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('device_limit_reached', true);
        $this->assertGuest();
    }

    public function test_login_with_revoke_others_clears_devices_and_authenticates(): void
    {
        $user = User::where('role', 'staff')->first();

        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            ['id' => 'dev_1', 'user_id' => $user->id, 'ip_address' => '192.168.1.1', 'user_agent' => 'Chrome', 'payload' => 'x', 'last_activity' => time()],
            ['id' => 'dev_2', 'user_id' => $user->id, 'ip_address' => '192.168.1.2', 'user_agent' => 'Safari', 'payload' => 'x', 'last_activity' => time()],
            ['id' => 'dev_3', 'user_id' => $user->id, 'ip_address' => '192.168.1.3', 'user_agent' => 'Edge', 'payload' => 'x', 'last_activity' => time()],
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
            'revoke_others' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        // Previous sessions should be cleared
        $this->assertEquals(0, \Illuminate\Support\Facades\DB::table('sessions')
            ->where('user_id', $user->id)
            ->whereIn('id', ['dev_1', 'dev_2', 'dev_3'])
            ->count());
    }
}
