<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alert_rules', function (Blueprint $table) {
            $table->foreignId('hazard_id')->nullable()->after('metric_definition_id')
                ->constrained('hazards')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('alert_rules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hazard_id');
        });
    }
};
