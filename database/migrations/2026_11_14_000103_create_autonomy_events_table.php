<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autonomy_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crew_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->string('event_type')->comment('comms_delay_start, emergency, decision_point, escalation');
            $table->unsignedInteger('comms_delay_seconds')->default(0)->comment('One-way delay to Earth');
            $table->boolean('handled_onboard')->default(false)->comment('Crew resolved issue independently');
            $table->boolean('escalated_to_earth')->default(false)->comment('Issue sent to Earth for guidance');
            $table->text('description')->nullable();
            $table->text('resolution')->nullable();
            $table->boolean('is_simulated')->default(true);
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
            
            $table->index(['crew_profile_id', 'recorded_at']);
            $table->index(['mission_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autonomy_events');
    }
};
