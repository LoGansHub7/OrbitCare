<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('baseline_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crew_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('metric_definition_id')->constrained()->restrictOnDelete();
            $table->decimal('baseline_value', 8, 2);
            $table->string('method', 40)->default('seeded');
            $table->date('reference_period_start');
            $table->date('reference_period_end');
            $table->unsignedInteger('min_sample_count')->default(3);
            $table->timestamps();

            $table->unique(['crew_profile_id', 'metric_definition_id'], 'baseline_profile_crew_metric_unique');
        });

        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('version', 20)->default('v1');
            $table->foreignId('metric_definition_id')->nullable()->constrained()->nullOnDelete();
            $table->string('severity', 30)->default('review');
            $table->json('parameters')->nullable();
            $table->text('explanation_template');
            $table->string('evidence_reference', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crew_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('alert_rule_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('open');
            $table->string('severity', 30)->default('review');
            $table->string('summary', 255);
            $table->text('explanation');
            $table->json('evidence')->nullable();
            $table->dateTime('first_detected_at');
            $table->dateTime('last_evaluated_at');
            $table->dateTime('acknowledged_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();

            $table->index(['crew_profile_id', 'status'], 'alerts_crew_status_idx');
        });

        Schema::create('followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('followups');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('alert_rules');
        Schema::dropIfExists('baseline_profiles');
    }
};
