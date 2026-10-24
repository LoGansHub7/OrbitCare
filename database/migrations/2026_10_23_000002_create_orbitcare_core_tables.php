<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->timestamps();
        });

        Schema::create('crew_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('mission_id')->constrained()->restrictOnDelete();
            $table->string('crew_code', 20)->unique();
            $table->timestamps();
        });

        Schema::create('metric_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label');
            $table->string('unit', 30);
            $table->decimal('min_value', 8, 2); // input plausibility bound (data quality, NOT a medical threshold)
            $table->decimal('max_value', 8, 2);
            $table->unsignedSmallInteger('freshness_hours');
            $table->timestamps();
        });

        Schema::create('health_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crew_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('metric_definition_id')->constrained()->restrictOnDelete();
            $table->decimal('value', 8, 2);
            $table->dateTime('observed_at'); // stored in UTC
            $table->string('source', 20);    // manual | csv_import | simulation
            $table->string('quality_flag', 20)->default('ok');
            $table->timestamps();

            $table->index(['crew_profile_id', 'metric_definition_id', 'observed_at'], 'readings_crew_metric_time_idx');
        });

        Schema::create('daily_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crew_profile_id')->constrained()->restrictOnDelete();
            $table->date('mission_date');
            $table->decimal('sleep_hours', 3, 1);
            $table->unsignedTinyInteger('sleep_quality');   // 1-5, 5 = best
            $table->unsignedTinyInteger('fatigue_rating');  // 1-5, 5 = most fatigued
            $table->unsignedSmallInteger('exercise_minutes');
            $table->text('symptom_notes')->nullable();
            $table->string('source', 20)->default('manual');
            $table->dateTime('submitted_at');
            $table->timestamps();

            $table->unique(['crew_profile_id', 'mission_date']); // one check-in per day
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('daily_checkins');
        Schema::dropIfExists('health_readings');
        Schema::dropIfExists('metric_definitions');
        Schema::dropIfExists('crew_profiles');
        Schema::dropIfExists('missions');
    }
};
