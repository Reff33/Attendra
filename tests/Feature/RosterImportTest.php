<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\AttendraDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RosterImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AttendraDemoSeeder::class);
    }

    public function test_staff_can_preview_and_auto_detect_csv_columns(): void
    {
        $staff = User::where('role', 'staff')->firstOrFail();
        $activity = Activity::where('user_id', $staff->id)->firstOrFail();

        $csvContent = "NAMA PELAJAR,NO MATRIK,NO KAD PENGENALAN,E-MEL,KURSUS\n" .
                      "AHMAD BIN ALI,B032110999,020515045123,ahmad@student.utem.edu.my,BITD\n" .
                      "SITI NURHALIZA,B032110888,030101016048,siti@student.utem.edu.my,BITS\n";

        $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

        $response = $this->actingAs($staff)->postJson(route('rosters.preview'), [
            'activity_id' => $activity->id,
            'roster_file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total_data_rows' => 2,
            ])
            ->assertJsonPath('detected.name', 0)
            ->assertJsonPath('detected.matric_no', 1)
            ->assertJsonPath('detected.ic_last_4', 2)
            ->assertJsonPath('detected.email', 3)
            ->assertJsonPath('detected.program', 4);
    }

    public function test_staff_can_execute_mapped_csv_import_with_database_commit(): void
    {
        $staff = User::where('role', 'staff')->firstOrFail();
        $activity = Activity::where('user_id', $staff->id)->firstOrFail();

        $csvContent = "Full Name,Matric No,IC No,Email,Program\n" .
                      "KHAIRUL ANUAR,B032210111,021111019999,khairul@utem.edu.my,BITM\n" .
                      "LEONG JIA WEI,B032210222,021212018888,leong@utem.edu.my,BITC\n";

        $response = $this->actingAs($staff)->post(route('rosters.import'), [
            'activity_id' => $activity->id,
            'raw_content' => base64_encode($csvContent),
            'filename' => 'cohort_test.csv',
            'col_name' => 0,
            'col_matric' => 1,
            'col_ic' => 2,
            'col_email' => 3,
            'col_program' => 4,
        ]);

        $response->assertRedirect(route('rosters.index', ['activity_id' => $activity->id]));
        $response->assertSessionHas('success');

        // Verify MariaDB participants table
        $this->assertDatabaseHas('participants', [
            'activity_id' => $activity->id,
            'matric_no' => 'B032210111',
            'name' => 'KHAIRUL ANUAR',
            'ic_last_4' => '9999',
            'program' => 'BITM',
        ]);

        $this->assertDatabaseHas('participants', [
            'activity_id' => $activity->id,
            'matric_no' => 'B032210222',
            'ic_last_4' => '8888',
        ]);

        // Verify SHA-256 Audit trail
        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'ROSTER_IMPORT_MAPPED',
        ]);
    }
}
