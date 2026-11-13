<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wellbeing_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crew_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('mood')->default(3)->comment('1-5 scale');
            $table->unsignedTinyInteger('stress')->default(3)->comment('1-5 scale');
            $table->decimal('sleep_hours', 4, 1)->default(8.0);
            $table->unsignedTinyInteger('sleep_quality')->default(3)->comment('1-5 scale');
            $table->unsignedInteger('crew_interaction_minutes')->default(60);
            $table->unsignedTinyInteger('workload')->default(3)->comment('1-5 scale');
            $table->text('notes')->nullable();
            $table->boolean('is_simulated')->default(true);
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
            
            $table->index(['crew_profile_id', 'recorded_at']);
            $table->index(['mission_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wellbeing_checkins');
    }
};
