<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_officer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('crew_profile_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['medical_officer_id', 'crew_profile_id'], 'care_assignments_officer_crew_unique');
            $table->index('crew_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_assignments');
    }
};
