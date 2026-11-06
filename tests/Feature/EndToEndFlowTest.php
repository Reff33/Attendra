<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AttendanceRecord;
use App\Models\EventSession;
use App\Models\User;
use Database\Seeders\AttendraDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Proves a staff member can go from zero to a working attendance session
 * without touching the database or a seeder.
 */
class EndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_run_full_activity_to_checkin_flow(): void
    {
        $this->seed(AttendraDemoSeeder::class);
        $staff = User::where('role', 'staff')->firstOrFail();

        // 1. Create the activity.
        $this->actingAs($staff)
            ->post(route('activities.store'), [
                'title' => 'Kursus Induksi Pelajar Baharu',
                'faculty' => 'FTMK',
            ])
            ->assertRedirect(route('activities.index'));

        $activity = Activity::where('title', 'Kursus Induksi Pelajar Baharu')->firstOrFail();

        // 2. Import its roster.
        $csv = "Full Name,Matric Number,IC Last 4 Digits,Program Code\n"
            ."NUR AISYAH BINTI HAMID,B032210001,1234,BITS\n"
            ."LIM WEI SHENG,B032210002,5678,BITD\n";

        $this->actingAs($staff)
            ->post(route('rosters.import'), [
                'activity_id' => $activity->id,
                'raw_content' => base64_encode($csv),
                'filename' => 'induksi.csv',
                'col_name' => 0,
                'col_matric' => 1,
                'col_ic' => 2,
                'col_program' => 3,
            ])
            ->assertRedirect();

        $this->assertSame(2, $activity->participants()->count(), 'Roster import did not persist participants.');

        // 3. Create the session on that activity.
        $this->actingAs($staff)
            ->post(route('sessions.store'), [
                'activity_id' => $activity->id,
                'title' => 'Sesi Pagi',
                'venue' => 'Dewan Kuliah 1',
                'verification_mode' => 'ic_last_4',
                'starts_at' => now()->subMinutes(5)->format('Y-m-d\TH:i'),
                'ends_at' => now()->addHours(2)->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('sessions.index'));

        $session = EventSession::where('activity_id', $activity->id)->firstOrFail();

        // 4. Student finds themselves in the roster.
        $this->get(route('public.roster-search', $session->access_token).'?q=B032210001')
            ->assertOk()
            ->assertJsonPath('participants.0.matric_no', 'B032210001');

        // 5. Student checks in.
        $this->post(route('public.checkin.submit', $session->access_token), [
            'matric_no' => 'B032210001',
            'ic_last_4' => '1234',
            'latitude' => $session->latitude,
            'longitude' => $session->longitude,
        ])->assertRedirect();

        $this->assertSame(1, AttendanceRecord::where('event_session_id', $session->id)->count());

        // 6. The matrix export reflects the check-in.
        $this->actingAs($staff)
            ->get(route('matrix.export', ['activity_id' => $activity->id]))
            ->assertOk();
    }
}
