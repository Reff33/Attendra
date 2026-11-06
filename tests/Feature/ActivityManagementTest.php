<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Database\Seeders\AttendraDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AttendraDemoSeeder::class);
        $this->staff = User::where('role', 'staff')->firstOrFail();
    }

    public function test_staff_can_create_activity(): void
    {
        $this->actingAs($this->staff)
            ->post(route('activities.store'), [
                'title' => 'Bengkel Keselamatan Siber 2026',
                'faculty' => 'FTMK',
                'description' => 'Two-day workshop',
            ])
            ->assertRedirect(route('activities.index'));

        $activity = Activity::where('title', 'Bengkel Keselamatan Siber 2026')->first();

        $this->assertNotNull($activity, 'Activity was not created.');
        $this->assertSame($this->staff->id, $activity->user_id, 'Activity must belong to its creator.');
        $this->assertSame('bengkel-keselamatan-siber-2026', $activity->slug);
        $this->assertSame('active', $activity->status);
    }

    public function test_slug_stays_unique_for_duplicate_titles(): void
    {
        $this->actingAs($this->staff)->post(route('activities.store'), ['title' => 'Majlis Amanat Dekan FTMK 2026']);

        $slugs = Activity::where('title', 'Majlis Amanat Dekan FTMK 2026')->pluck('slug');

        $this->assertCount(2, $slugs);
        $this->assertSame($slugs->unique()->count(), $slugs->count(), 'Slugs must not collide.');
    }

    public function test_title_is_required(): void
    {
        $this->actingAs($this->staff)
            ->post(route('activities.store'), ['title' => ''])
            ->assertSessionHasErrors('title');
    }

    public function test_staff_sees_only_own_activities_and_admin_sees_all(): void
    {
        $other = User::where('role', 'staff')->where('id', '!=', $this->staff->id)->firstOrFail();
        $ownActivity = Activity::where('user_id', $this->staff->id)->firstOrFail();
        $foreignActivity = Activity::where('user_id', $other->id)->firstOrFail();

        $this->actingAs($this->staff)
            ->get(route('activities.index'))
            ->assertOk()
            ->assertSee($ownActivity->title)
            ->assertDontSee($foreignActivity->title);

        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('activities.index'))
            ->assertOk()
            ->assertSee($ownActivity->title)
            ->assertSee($foreignActivity->title);
    }

    public function test_staff_cannot_edit_another_staff_activity(): void
    {
        $other = User::where('role', 'staff')->where('id', '!=', $this->staff->id)->firstOrFail();
        $foreign = Activity::where('user_id', $other->id)->firstOrFail();

        $this->actingAs($this->staff)->get(route('activities.edit', $foreign))->assertForbidden();

        $this->actingAs($this->staff)
            ->put(route('activities.update', $foreign), ['title' => 'Hijacked'])
            ->assertForbidden();

        $this->assertNotSame('Hijacked', $foreign->fresh()->title);
    }

    public function test_staff_can_update_and_archive_own_activity(): void
    {
        $activity = Activity::where('user_id', $this->staff->id)->firstOrFail();

        $this->actingAs($this->staff)
            ->put(route('activities.update', $activity), [
                'title' => 'Renamed Activity',
                'faculty' => 'FTMK',
            ])
            ->assertRedirect(route('activities.index'));

        $this->assertSame('Renamed Activity', $activity->fresh()->title);

        $this->actingAs($this->staff)
            ->post(route('activities.toggle', $activity))
            ->assertRedirect();

        $this->assertSame('archived', $activity->fresh()->status);

        $this->actingAs($this->staff)
            ->post(route('activities.toggle', $activity))
            ->assertRedirect();

        $this->assertSame('active', $activity->fresh()->status);
    }

    public function test_activity_creation_is_audited(): void
    {
        $this->actingAs($this->staff)
            ->post(route('activities.store'), ['title' => 'Audited Activity']);

        $activity = Activity::where('title', 'Audited Activity')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'ACTIVITY_CREATE',
            'target_entity' => 'Activity',
            'target_id' => (string) $activity->id,
        ]);
    }
}
