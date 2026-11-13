<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            if (!Schema::hasColumn('missions', 'mission_profile')) {
                $table->enum('mission_profile', ['LEO', 'Moon', 'Mars'])->default('LEO')->after('name');
            }
            if (!Schema::hasColumn('missions', 'hazard_weightings')) {
                $table->json('hazard_weightings')->nullable()->after('mission_profile')
                    ->comment('{"radiation": 0.8, "isolation": 1.0, ...}');
            }
        });
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropColumn('mission_profile', 'hazard_weightings');
        });
    }
};
