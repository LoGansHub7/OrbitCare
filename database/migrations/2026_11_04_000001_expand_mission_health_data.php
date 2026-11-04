<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metric_definitions', function (Blueprint $table) {
            $table->string('category', 50)->default('general')->after('label');
            $table->string('direction_of_concern', 20)->default('higher')->after('unit');
            $table->string('scale_description', 255)->nullable()->after('direction_of_concern');
        });

        Schema::table('daily_checkins', function (Blueprint $table) {
            $table->unsignedTinyInteger('mood_rating')->nullable()->after('fatigue_rating');
            $table->unsignedTinyInteger('stress_rating')->nullable()->after('mood_rating');
            $table->unsignedTinyInteger('social_connectedness_rating')->nullable()->after('stress_rating');
            $table->unsignedSmallInteger('planned_aerobic_minutes')->nullable()->after('exercise_minutes');
            $table->unsignedSmallInteger('completed_aerobic_minutes')->nullable()->after('planned_aerobic_minutes');
            $table->unsignedSmallInteger('planned_resistive_minutes')->nullable()->after('completed_aerobic_minutes');
            $table->unsignedSmallInteger('completed_resistive_minutes')->nullable()->after('planned_resistive_minutes');
            $table->json('symptom_tags')->nullable()->after('symptom_notes');
        });

        Schema::create('alert_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained()->restrictOnDelete();
            $table->json('snapshot');
            $table->timestamps();
        });
        Schema::create('mission_events', function (Blueprint $table) {
            $table->id(); $table->foreignId('mission_id')->constrained()->restrictOnDelete();
            $table->string('name'); $table->dateTime('occurred_at'); $table->text('notes')->nullable(); $table->timestamps();
        });
        Schema::create('research_sources', function (Blueprint $table) {
            $table->id(); $table->string('title'); $table->string('publisher'); $table->string('url', 2048);
            $table->text('notes')->nullable(); $table->date('verified_at')->nullable(); $table->timestamps();
        });
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id(); $table->foreignId('uploader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_name'); $table->string('source', 20); $table->string('status', 30);
            $table->unsignedInteger('row_count')->default(0); $table->unsignedInteger('imported_count')->default(0); $table->json('errors')->nullable(); $table->timestamps();
        });
        Schema::create('symptom_logs', function (Blueprint $table) {
            $table->id(); $table->foreignId('crew_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('daily_checkin_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tag', 50); $table->string('source', 20)->default('manual'); $table->timestamps();
            $table->index(['crew_profile_id', 'tag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('symptom_logs'); Schema::dropIfExists('import_batches'); Schema::dropIfExists('research_sources'); Schema::dropIfExists('mission_events'); Schema::dropIfExists('alert_evidence');
        Schema::table('daily_checkins', fn (Blueprint $table) => $table->dropColumn(['mood_rating','stress_rating','social_connectedness_rating','planned_aerobic_minutes','completed_aerobic_minutes','planned_resistive_minutes','completed_resistive_minutes','symptom_tags']));
        Schema::table('metric_definitions', fn (Blueprint $table) => $table->dropColumn(['category','direction_of_concern','scale_description']));
    }
};
