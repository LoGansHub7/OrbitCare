<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physical_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crew_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('exercise_minutes')->default(0)->comment('Daily exercise/countermeasure time');
            $table->decimal('muscle_index', 5, 2)->nullable()->comment('Estimated muscle mass index');
            $table->decimal('bone_index', 5, 2)->nullable()->comment('Bone density index');
            $table->unsignedTinyInteger('balance_score')->nullable()->default(5)->comment('1-10 scale, higher is better');
            $table->text('symptoms')->nullable()->comment('Reported symptoms (headache, dizziness, muscle pain, etc.)');
            $table->boolean('is_simulated')->default(true);
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
            
            $table->index(['crew_profile_id', 'recorded_at']);
            $table->index(['mission_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physical_readings');
    }
};
