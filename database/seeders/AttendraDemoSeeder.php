<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\Roster;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AttendraDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Administrator (PRD §5.1 / FigCraft D9)
        $admin = User::updateOrCreate(
            ['email' => 'admin@utem.edu.my'],
            [
                'name' => 'Prof. Dr. Syahril Admin',
                'staff_id' => 'STF-ADM-001',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'faculty' => 'Chancellery / Central IT',
                'phone' => '+6012-3456789',
                'status' => 'active',
            ]
        );

        // 2. Create Staff Organizers (PRD §5.1 / FigCraft D10)
        $faris = User::updateOrCreate(
            ['email' => 'faris.razali@utem.edu.my'],
            [
                'name' => 'Faris Razali',
                'staff_id' => 'STF-FTMK-009',
                'password' => Hash::make('password123'),
                'role' => 'staff',
                'faculty' => 'FTMK • Software Eng.',
                'phone' => '+6013-9876543',
                'status' => 'active',
            ]
        );

        $aminah = User::updateOrCreate(
            ['email' => 'aminah.kassim@utem.edu.my'],
            [
                'name' => 'Dr. Siti Aminah binti Kassim',
                'staff_id' => 'STF-FKE-004',
                'password' => Hash::make('password123'),
                'role' => 'staff',
                'faculty' => 'FKE • Electrical Eng.',
                'phone' => '+6017-8899001',
                'status' => 'active',
            ]
        );

        $zulkifli = User::updateOrCreate(
            ['email' => 'zulkifli.saad@utem.edu.my'],
            [
                'name' => 'Prof. Madya Dr. Zulkifli bin Saad',
                'staff_id' => 'STF-FKM-002',
                'password' => Hash::make('password123'),
                'role' => 'staff',
                'faculty' => 'FKM • Mechanical Eng.',
                'phone' => '+6019-1122334',
                'status' => 'active',
            ]
        );

        // 3. Create Activities (PRD §8.3 / FigCraft D3, D5, D11)
        $activity1 = Activity::create([
            'user_id' => $faris->id,
            'title' => 'Majlis Amanat Dekan FTMK 2026',
            'slug' => 'majlis-amanat-dekan-ftmk-2026',
            'faculty' => 'FTMK • Fakulti Teknologi Maklumat & Komunikasi',
            'description' => 'Amanat tahunan bersama Dekan FTMK & taklimat akademik semester baharu.',
            'status' => 'active',
        ]);

        $activity2 = Activity::create([
            'user_id' => $faris->id,
            'title' => 'Bengkel AI & Data Engineering 2026',
            'slug' => 'bengkel-ai-data-engineering-2026',
            'faculty' => 'FTMK • Fakulti Teknologi Maklumat & Komunikasi',
            'description' => 'Hands-on workshop teknologi AI dan pipeline data untuk pelajar tahun 3 & 4.',
            'status' => 'active',
        ]);

        $activity3 = Activity::create([
            'user_id' => $aminah->id,
            'title' => 'Bengkel Algoritma & Struktur Data (Kump. 2)',
            'slug' => 'bengkel-algoritma-struktur-data-k2',
            'faculty' => 'FKE • Fakulti Kejuruteraan Elektrik',
            'description' => 'Sesi praktikal algoritma dan pengoptimuman memori sistem.',
            'status' => 'active',
        ]);

        // 4. Create Roster for Activity 1 (FigCraft D5)
        $roster1 = Roster::create([
            'activity_id' => $activity1->id,
            'filename' => 'FTMK_Year3_Session2026.xlsx',
            'dataset_label' => 'FTMK_Year3_Session2026.xlsx',
            'total_records' => 450,
            'is_frozen' => true,
        ]);

        // Real sample students from FigCraft D5
        $students = [
            ['name' => 'Ahmad Danial bin Razali', 'matric' => 'B032110045', 'ic' => '4312', 'prog' => 'BITS (Software Engineering)'],
            ['name' => 'Nurul Izzah binti Mansor', 'matric' => 'B032110112', 'ic' => '6678', 'prog' => 'BITD (Data Science)'],
            ['name' => 'Chong Wei Lun', 'matric' => 'B032110088', 'ic' => '1024', 'prog' => 'BITM (Cybersecurity)'],
            ['name' => 'Siti Khadijah binti Othman', 'matric' => 'B032110034', 'ic' => '9088', 'prog' => 'BITZ (Game Development)'],
            ['name' => 'Kavitha a/p Subramaniam', 'matric' => 'B032110199', 'ic' => '5521', 'prog' => 'BITC (Computer Networking)'],
            ['name' => 'Muhammad Arif bin Zainal', 'matric' => 'B032110450', 'ic' => '7841', 'prog' => 'BITC (Computer Networking)'],
            ['name' => 'Tan Mei Ling', 'matric' => 'B032110321', 'ic' => '3321', 'prog' => 'BITS (Software Engineering)'],
            ['name' => 'Hafizuddin bin Azman', 'matric' => 'B032110214', 'ic' => '8890', 'prog' => 'BITD (Data Science)'],
        ];

        $participantRecords = [];
        foreach ($students as $s) {
            $participantRecords[$s['matric']] = Participant::create([
                'roster_id' => $roster1->id,
                'activity_id' => $activity1->id,
                'name' => $s['name'],
                'matric_no' => $s['matric'],
                'ic_last_4' => $s['ic'],
                'program' => $s['prog'],
                'email' => strtolower(str_replace(' ', '', $s['matric'])) . '@student.utem.edu.my',
            ]);
        }

        // 5. Create Sessions (FigCraft D3 / D4 / D7)
        // Session 1: Live Now
        $session1 = EventSession::create([
            'activity_id' => $activity1->id,
            'title' => 'Majlis Amanat Dekan FTMK 2026',
            'venue' => 'Dewan Al-Jazari, FTMK',
            'access_token' => 'amanat-ftmk-2026',
            'status' => 'open', // LIVE
            'verification_mode' => 'ic_and_gps',
            'latitude' => 2.3138890,
            'longitude' => 102.3211110,
            'geofence_radius_meters' => 100,
            'starts_at' => now()->setTime(8, 30),
            'ends_at' => now()->setTime(11, 30),
        ]);

        // Session 2: Upcoming
        $session2 = EventSession::create([
            'activity_id' => $activity1->id,
            'title' => 'Bengkel AI & Data Engineering: Sesi Petang',
            'venue' => 'Makmal Siber 3, FTMK',
            'access_token' => 'ai-data-2026',
            'status' => 'scheduled',
            'verification_mode' => 'ic_last_4',
            'latitude' => 2.3145000,
            'longitude' => 102.3215000,
            'geofence_radius_meters' => 50,
            'starts_at' => now()->addDay()->setTime(14, 0),
            'ends_at' => now()->addDay()->setTime(17, 0),
        ]);

        // 6. Record Sample Attendance (FigCraft D3 Live Stream / D6 Matrix)
        AttendanceRecord::create([
            'event_session_id' => $session1->id,
            'participant_id' => $participantRecords['B032110045']->id,
            'student_name' => 'Ahmad Danial bin Razali',
            'matric_no' => 'B032110045',
            'verification_method' => 'ic_last_4',
            'status' => 'present',
            'is_walk_in' => false,
            'token_hash' => 'AKT1-4312-' . Str::random(6),
            'ip_address' => '10.20.14.88',
            'recorded_latitude' => 2.313910,
            'recorded_longitude' => 102.321140,
            'distance_meters' => 4.2,
            'checked_in_at' => now()->subMinutes(18),
        ]);

        AttendanceRecord::create([
            'event_session_id' => $session1->id,
            'participant_id' => $participantRecords['B032110112']->id,
            'student_name' => 'Nurul Izzah binti Mansor',
            'matric_no' => 'B032110112',
            'verification_method' => 'gps',
            'status' => 'present',
            'is_walk_in' => false,
            'token_hash' => 'AKT1-6678-' . Str::random(6),
            'ip_address' => '10.20.14.92',
            'recorded_latitude' => 2.313880,
            'recorded_longitude' => 102.321100,
            'distance_meters' => 3.1,
            'checked_in_at' => now()->subMinutes(12),
        ]);

        // Walk-In attendance sample (FigCraft D7)
        AttendanceRecord::create([
            'event_session_id' => $session1->id,
            'participant_id' => null,
            'student_name' => 'Muhammad Harith bin Zulkifli',
            'matric_no' => 'B032110290',
            'verification_method' => 'manual_walkin',
            'status' => 'walk_in',
            'is_walk_in' => true,
            'override_reason' => 'Late Roster Add / Walk-In Special Approval by Host of Event',
            'token_hash' => 'WALK-0290-' . Str::random(6),
            'ip_address' => '10.20.14.10',
            'checked_in_at' => now()->subMinutes(5),
        ]);

        // 7. Audit Logs (FigCraft D8)
        AuditLog::record(
            'SESSION_OPEN',
            'EventSession',
            (string) $session1->id,
            'Session opened for public attendance check-in (Policy: 4-Digit IC + GPS 100m)',
            $faris
        );

        AuditLog::record(
            'MANUAL_WALKIN',
            'AttendanceRecord',
            'B032110290',
            'Manual walk-in approval for Muhammad Harith bin Zulkifli (Matric: B032110290)',
            $faris
        );

        AuditLog::record(
            'ROSTER_IMPORT',
            'Roster',
            (string) $roster1->id,
            'Streamed batch parser loaded 450 student records from FTMK_Year3_Session2026.xlsx',
            $faris
        );
    }
}
