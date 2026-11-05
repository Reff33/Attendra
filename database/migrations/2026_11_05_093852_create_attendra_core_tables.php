<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Extend users table for Attendra roles and faculties
        Schema::table('users', function (Blueprint $table) {
            $table->string('staff_id')->nullable()->unique()->after('id');
            $table->string('role')->default('staff')->after('password'); // admin | staff
            $table->string('faculty')->nullable()->after('role'); // FTMK, FKE, FKM, etc.
            $table->string('phone')->nullable()->after('faculty');
            $table->string('status')->default('active')->after('phone'); // active | suspended
        });

        // 2. Activities (Parent event/project)
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Organizer staff/admin
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('faculty')->nullable();
            $table->text('description')->nullable();
            $table->string('banner_path')->nullable();
            $table->string('status')->default('active'); // draft | active | archived
            $table->timestamps();
        });

        // 3. Rosters (Uploaded datasets per activity)
        Schema::create('rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('dataset_label'); // e.g. FTMK_Year3_Session2026.xlsx
            $table->integer('total_records')->default(0);
            $table->boolean('is_frozen')->default(true); // Frozen on publish (PRD §2.2 A06)
            $table->timestamps();
        });

        // 4. Participants (Members of the roster)
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('matric_no')->index();
            $table->string('ic_last_4', 4)->nullable(); // Option A 4-digit verification
            $table->string('program')->nullable(); // Kursus/Course
            $table->string('email')->nullable();
            $table->timestamps();

            // Unique matric inside the same activity dataset
            $table->unique(['activity_id', 'matric_no'], 'activity_matric_unique');
        });

        // 5. Event Attendance Sessions (e.g. Morning, Afternoon sessions)
        Schema::create('event_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('title'); // e.g. Majlis Amanat Dekan FTMK 2026
            $table->string('venue')->nullable(); // e.g. Dewan Al-Jazari, FTMK
            $table->string('access_token')->unique(); // Unique public check-in token / URL hash
            $table->string('status')->default('scheduled'); // scheduled | open | closed | disabled
            $table->string('verification_mode')->default('ic_last_4'); // none | ic_last_4 | gps | ic_and_gps
            $table->decimal('latitude', 10, 7)->nullable(); // Geofence center
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('geofence_radius_meters')->default(100); // 50m | 100m | 200m
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->timestamps();
        });

        // 6. Attendance Records (Actual logs submitted)
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->nullable()->constrained()->nullOnDelete();
            // In case of walk-in without prior participant roster entry
            $table->string('student_name');
            $table->string('matric_no')->index();
            $table->string('verification_method')->default('ic_last_4'); // ic_last_4 | gps | qr_staff_scan | manual_walkin
            $table->string('status')->default('present'); // present | late | walk_in | excused
            $table->boolean('is_walk_in')->default(false);
            $table->string('override_reason')->nullable(); // Mandatory reason if manual override (PRD §6.2)
            $table->string('token_hash')->nullable(); // SHA-256 digital pass proof
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->decimal('recorded_latitude', 10, 7)->nullable();
            $table->decimal('recorded_longitude', 10, 7)->nullable();
            $table->decimal('distance_meters', 8, 2)->nullable();
            $table->timestamp('checked_in_at')->useCurrent();
            $table->timestamps();

            // STRICT UNIQUE CONSTRAINT: Duplicate prevention (PRD §2.2 / §4)
            $table->unique(['event_session_id', 'matric_no'], 'session_matric_unique');
        });

        // 7. Cryptographic Immutable Audit Logs (PRD §5.2 / §8.3 / §14)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Staff/Admin who performed action
            $table->string('action_type'); // SESSION_OPEN | SESSION_CLOSE | MANUAL_WALKIN | ROSTER_IMPORT | RECORD_CORRECTION
            $table->string('actor_name');
            $table->string('actor_staff_id')->nullable();
            $table->string('target_entity'); // Participant / Session
            $table->string('target_id')->nullable();
            $table->text('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('sha256_hash'); // Immutable audit hash signature
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('event_sessions');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('rosters');
        Schema::dropIfExists('activities');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['staff_id', 'role', 'faculty', 'phone', 'status']);
        });
    }
};
