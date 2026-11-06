<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Database\Seeders\AttendraDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class XlsxImportTest extends TestCase
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

    /**
     * Build a minimal but valid .xlsx (ZIP + worksheet XML) using inline strings.
     */
    private function xlsx(array $rows): string
    {
        $sheet = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($rows as $r => $cells) {
            $sheet .= '<row r="'.($r + 1).'">';
            foreach ($cells as $c => $value) {
                $ref = chr(65 + $c).($r + 1);
                $sheet .= '<c r="'.$ref.'" t="inlineStr"><is><t>'.htmlspecialchars($value).'</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData></worksheet>';

        $path = tempnam(sys_get_temp_dir(), 'attendra_test_').'.xlsx';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        $content = file_get_contents($path);
        @unlink($path);

        return $content;
    }

    public function test_xlsx_roster_is_imported_like_csv(): void
    {
        $content = $this->xlsx([
            ['Full Name', 'Matric Number', 'IC Last 4 Digits', 'Program Code'],
            ['SITI NURHALIZA BINTI OMAR', 'B032210777', '4455', 'BITS'],
            ['GOH CHUN KEAT', 'B032210778', '8899', 'BITD'],
        ]);

        $this->actingAs($this->owner)
            ->post(route('rosters.import'), [
                'activity_id' => $this->activity->id,
                'raw_content' => base64_encode($content),
                'filename' => 'cohort.xlsx',
                'col_name' => 0,
                'col_matric' => 1,
                'col_ic' => 2,
                'col_program' => 3,
            ])
            ->assertRedirect();

        $this->assertSame(2, $this->activity->participants()->count());
        $this->assertDatabaseHas('participants', [
            'activity_id' => $this->activity->id,
            'matric_no' => 'B032210777',
            'name' => 'SITI NURHALIZA BINTI OMAR',
            'ic_last_4' => '4455',
        ]);
    }

    public function test_xlsx_preview_reports_headers_and_rows(): void
    {
        $content = $this->xlsx([
            ['Student Name', 'No Matrik', 'Email'],
            ['AHMAD ZAKI', 'B032210900', 'zaki@student.utem.edu.my'],
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('cohort.xlsx', $content);

        $response = $this->actingAs($this->owner)
            ->post(route('rosters.preview'), [
                'activity_id' => $this->activity->id,
                'roster_file' => $file,
            ])
            ->assertOk();

        $response->assertJsonPath('success', true);
        $response->assertJsonPath('headers.0.label', 'Student Name');
        $response->assertJsonPath('sample_rows.0.1', 'B032210900');
        $response->assertJsonPath('detected.matric_no', 1);
    }
}
