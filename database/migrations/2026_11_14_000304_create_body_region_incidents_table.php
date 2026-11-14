<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Stores outcomes from the "Something hurts here" guided wizard so they appear in reports.
    public function up(): void
    {
        Schema::create('body_region_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crew_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('body_region_id')->constrained()->cascadeOnDelete();
            $table->string('onset');
            $table->unsignedTinyInteger('severity');
            $table->json('associated_symptoms')->nullable();
            $table->string('result_level');
            $table->json('answers');
            $table->timestamp('recorded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('body_region_incidents');
    }
};
