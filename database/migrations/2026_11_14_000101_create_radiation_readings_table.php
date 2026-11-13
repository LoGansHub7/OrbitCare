<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiation_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crew_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->decimal('dose_rate', 8, 4)->comment('mSv/day');
            $table->decimal('neutron_dose', 8, 4)->nullable()->comment('Neutrons, mSv');
            $table->decimal('other_dose', 8, 4)->nullable()->comment('Other radiation, mSv');
            $table->decimal('cumulative_dose', 10, 4)->comment('Total cumulative mSv');
            $table->boolean('is_simulated')->default(true);
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
            
            $table->index(['crew_profile_id', 'recorded_at']);
            $table->index(['mission_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiation_readings');
    }
};
