<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('body_region_guidance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('body_region_id')->constrained()->cascadeOnDelete();
            // Status levels mirror the app-wide statuses: no_flag, review, priority_review, insufficient_data.
            // We label them nominal/watch/review/urgent in the guidance UI per the Body Map brief,
            // mapped from the underlying statuses in BodyMapService.
            $table->string('status_level');
            $table->json('self_care_steps');
            $table->json('red_flags');
            $table->string('escalate_to');
            $table->json('sources')->nullable();
            $table->timestamps();
            $table->unique(['body_region_id', 'status_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('body_region_guidance');
    }
};
