<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('body_region_hazards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('body_region_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hazard_id')->constrained()->cascadeOnDelete();
            $table->text('why_it_matters');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['body_region_id', 'hazard_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('body_region_hazards');
    }
};
