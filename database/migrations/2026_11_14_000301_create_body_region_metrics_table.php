<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('body_region_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('body_region_id')->constrained()->cascadeOnDelete();
            // One of these two is set: a MetricDefinition-backed reading, or a named hazard-table source
            // (e.g. 'radiation_dose', 'wellbeing_mood', 'physical_exercise', 'cabin_co2').
            $table->foreignId('metric_definition_id')->nullable()->constrained()->nullOnDelete();
            $table->string('hazard_metric_code')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('body_region_metrics');
    }
};
