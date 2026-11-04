<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('missions', function (Blueprint $table) { $table->string('earth_link_status', 20)->default('online'); $table->unsignedInteger('earth_link_delay_minutes')->default(0); $table->dateTime('earth_link_changed_at')->nullable(); });
        Schema::table('followups', function (Blueprint $table) { $table->boolean('share_with_earth')->default(false); });
        Schema::create('earth_sync_packages', function (Blueprint $table) { $table->id(); $table->foreignId('mission_id')->constrained()->restrictOnDelete(); $table->foreignId('crew_profile_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('followup_id')->nullable()->constrained()->nullOnDelete(); $table->string('status', 20)->default('queued'); $table->json('payload'); $table->dateTime('available_at')->nullable(); $table->dateTime('sent_at')->nullable(); $table->timestamps(); });
    }
    public function down(): void { Schema::dropIfExists('earth_sync_packages'); Schema::table('followups', fn(Blueprint $table) => $table->dropColumn('share_with_earth')); Schema::table('missions', fn(Blueprint $table) => $table->dropColumn(['earth_link_status','earth_link_delay_minutes','earth_link_changed_at'])); }
};
