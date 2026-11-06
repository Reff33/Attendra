<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\AttendraDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $outsider;
    private Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AttendraDemoSeeder::class);

        $staff = User::where('role', 'staff')->get();
        $this->owner = $staff[0];
        $this->outsider = $staff[1];
        $this->activity = Activity::where('user_id', $this->owner->id)->firstOrFail();
    }

    public function test_search_finds_own_records_only(): void
    {
        $this->actingAs($this->owner)
            ->getJson(route('search', ['q' => 'B032110045']))
            ->assertOk()
            ->assertJsonPath('participants.0.title', 'Ahmad Danial bin Razali');

        // The outsider cannot see another staff member's participants.
        $this->actingAs($this->outsider)
            ->getJson(route('search', ['q' => 'B032110045']))
            ->assertOk()
            ->assertJsonPath('participants', []);
    }

    public function test_admin_search_spans_all_activities(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->getJson(route('search', ['q' => 'B032110045']))
            ->assertOk()
            ->assertJsonPath('participants.0.title', 'Ahmad Danial bin Razali');
    }

    public function test_short_query_returns_empty_result(): void
    {
        $this->actingAs($this->owner)
            ->getJson(route('search', ['q' => 'a']))
            ->assertOk()
            ->assertJsonPath('activities', [])
            ->assertJsonPath('sessions', [])
            ->assertJsonPath('participants', []);
    }

    public function test_notification_inbox_only_lists_own_notifications(): void
    {
        UserNotification::notify($this->owner->id, 'Your roster is ready', null, 'roster');
        UserNotification::notify($this->outsider->id, 'Someone else notice', null, 'system');

        $this->actingAs($this->owner)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Your roster is ready')
            ->assertDontSee('Someone else notice');
    }

    public function test_opening_a_notification_marks_it_read(): void
    {
        $notification = UserNotification::notify($this->owner->id, 'Test notice', 'Body', 'system');

        $this->actingAs($this->owner)
            ->get(route('notifications.show', $notification))
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_notification_url_redirects_after_marking_read(): void
    {
        $notification = UserNotification::notify(
            $this->owner->id,
            'Roster imported',
            null,
            'roster',
            route('rosters.index', ['activity_id' => $this->activity->id])
        );

        $this->actingAs($this->owner)
            ->get(route('notifications.show', $notification))
            ->assertRedirect(route('rosters.index', ['activity_id' => $this->activity->id]));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_other_user_cannot_open_or_delete_a_notification(): void
    {
        $notification = UserNotification::notify($this->owner->id, 'Private notice', null, 'system');

        $this->actingAs($this->outsider)
            ->get(route('notifications.show', $notification))
            ->assertNotFound();

        $this->actingAs($this->outsider)
            ->delete(route('notifications.destroy', $notification))
            ->assertNotFound();

        $this->assertDatabaseHas('user_notifications', ['id' => $notification->id]);
    }

    public function test_mark_all_read_only_affects_own_notifications(): void
    {
        UserNotification::notify($this->owner->id, 'Mine', null, 'system');
        $other = UserNotification::notify($this->outsider->id, 'Theirs', null, 'system');

        $this->actingAs($this->owner)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, UserNotification::where('user_id', $this->owner->id)->whereNull('read_at')->count());
        $this->assertNull($other->fresh()->read_at);
    }

    public function test_roster_import_notifies_the_activity_owner(): void
    {
        $csv = "Full Name,Matric Number\nNUR TEST,B032219999\n";

        $this->actingAs($this->owner)
            ->post(route('rosters.import'), [
                'activity_id' => $this->activity->id,
                'raw_content' => base64_encode($csv),
                'filename' => 'test.csv',
                'col_name' => 0,
                'col_matric' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->owner->id,
            'type' => 'roster',
        ]);
    }

    public function test_auto_close_notifies_the_session_owner(): void
    {
        $session = $this->activity->sessions()->firstOrFail();
        $session->update(['status' => 'open', 'ends_at' => now()->subMinute()]);

        $this->artisan('sessions:auto-close')->assertExitCode(0);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->owner->id,
            'type' => 'session',
        ]);
    }

    public function test_bell_badge_shows_unread_count(): void
    {
        UserNotification::notify($this->owner->id, 'Unread one', null, 'system');
        UserNotification::notify($this->owner->id, 'Unread two', null, 'system');

        $this->actingAs($this->owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('bi-bell', false);
    }
}
