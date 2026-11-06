<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Database\Seeders\AttendraDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleSheetRosterTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AttendraDemoSeeder::class);

        $this->owner = User::where('role', 'staff')->firstOrFail();
        $this->activity = Activity::where('user_id', $this->owner->id)->firstOrFail();
    }

    public function test_public_sheet_link_is_exported_to_csv_and_previewed(): void
    {
        Http::fake([
            'docs.google.com/*' => Http::response(
                "Full Name,Matric Number,IC Last 4 Digits\nNURUL HUDA,B032210555,1234\n",
                200
            ),
        ]);

        $this->actingAs($this->owner)
            ->post(route('rosters.preview'), [
                'activity_id' => $this->activity->id,
                'sheet_url' => 'https://docs.google.com/spreadsheets/d/1AbCdEfGhIjK/edit#gid=123',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('file_name', 'google-sheet-1AbCdEfGhIjK.csv')
            ->assertJsonPath('headers.0.label', 'Full Name')
            ->assertJsonPath('detected.matric_no', 1)
            ->assertJsonPath('total_data_rows', 1);

        Http::assertSent(fn ($req) => str_contains($req->url(), '/1AbCdEfGhIjK/export')
            && str_contains($req->url(), 'format=csv')
            && str_contains($req->url(), 'gid=123'));
    }

    public function test_sheet_link_is_imported_after_preview(): void
    {
        Http::fake([
            'docs.google.com/*' => Http::response(
                "Full Name,Matric Number,IC Last 4 Digits\nNURUL HUDA,B032210555,1234\n",
                200
            ),
        ]);

        $preview = $this->actingAs($this->owner)
            ->post(route('rosters.preview'), [
                'activity_id' => $this->activity->id,
                'sheet_url' => 'https://docs.google.com/spreadsheets/d/1AbCdEfGhIjK/edit',
            ])
            ->assertOk()
            ->json();

        $this->actingAs($this->owner)
            ->post(route('rosters.import'), [
                'activity_id' => $this->activity->id,
                'raw_content' => $preview['raw_content'],
                'filename' => $preview['file_name'],
                'col_name' => 0,
                'col_matric' => 1,
                'col_ic' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('participants', [
            'activity_id' => $this->activity->id,
            'matric_no' => 'B032210555',
        ]);
    }

    public function test_private_sheet_html_response_is_rejected(): void
    {
        Http::fake([
            'docs.google.com/*' => Http::response('<html><body>Sign in</body></html>', 200),
        ]);

        $this->actingAs($this->owner)
            ->post(route('rosters.preview'), [
                'activity_id' => $this->activity->id,
                'sheet_url' => 'https://docs.google.com/spreadsheets/d/1PrivateSheet/edit',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_non_google_url_is_rejected_without_a_request(): void
    {
        Http::fake();

        $this->actingAs($this->owner)
            ->post(route('rosters.preview'), [
                'activity_id' => $this->activity->id,
                'sheet_url' => 'http://169.254.169.254/latest/meta-data/',
            ])
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_staff_cannot_preview_into_another_staff_activity(): void
    {
        $outsider = User::where('role', 'staff')->where('id', '!=', $this->owner->id)->firstOrFail();
        Http::fake();

        $this->actingAs($outsider)
            ->post(route('rosters.preview'), [
                'activity_id' => $this->activity->id,
                'sheet_url' => 'https://docs.google.com/spreadsheets/d/1AbCdEfGhIjK/edit',
            ])
            ->assertForbidden();

        Http::assertNothingSent();
    }
}
