<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cabin_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->decimal('temperature', 5, 2)->comment('°C');
            $table->decimal('pressure', 6, 2)->comment('kPa');
            $table->decimal('co2_level', 6, 1)->comment('ppm');
            $table->unsignedInteger('light_level')->comment('lux');
            $table->unsignedInteger('noise_db')->comment('dB');
            $table->decimal('microbial_count', 8, 2)->nullable()->comment('CFU/mL');
            $table->boolean('is_simulated')->default(true);
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
            
            $table->index(['mission_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cabin_readings');
    }
};
