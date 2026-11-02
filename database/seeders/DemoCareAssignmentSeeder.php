<?php

namespace Database\Seeders;

use App\Models\CrewProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoCareAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $medicalOfficer = User::where('email', 'medic@orbitcare.test')->firstOrFail();
        $crewProfiles = CrewProfile::whereIn('crew_code', ['OC-01', 'OC-02', 'OC-03'])->get();

        foreach ($crewProfiles as $crewProfile) {
            $medicalOfficer->assignedCrewProfiles()->syncWithoutDetaching([$crewProfile->id]);
        }
    }
}
