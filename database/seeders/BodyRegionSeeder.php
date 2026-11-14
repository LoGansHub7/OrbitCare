<?php

namespace Database\Seeders;

use App\Models\BodyRegion;
use App\Models\Hazard;
use App\Models\MetricDefinition;
use Illuminate\Database\Seeder;

class BodyRegionSeeder extends Seeder
{
    public function run(): void
    {
        $hr = MetricDefinition::where('code', 'resting_heart_rate')->value('id');
        $sleep = MetricDefinition::where('code', 'sleep_duration')->value('id');
        $fatigue = MetricDefinition::where('code', 'fatigue_rating')->value('id');

        $hazardIds = Hazard::pluck('id', 'slug');

        $regions = [
            [
                'slug' => 'head-brain',
                'name' => 'Head & Brain',
                'body_system' => 'neurological',
                'mesh_name' => 'Head',
                'svg_id' => 'region-head',
                'function_text' => 'Coordinates movement, cognition, mood, and sleep regulation. Early signs of fatigue, headaches, or mood change often show up here first.',
                'sort_order' => 1,
                'metrics' => [
                    ['metric_definition_id' => $sleep],
                    ['metric_definition_id' => $fatigue],
                    ['hazard_metric_code' => 'wellbeing_mood'],
                ],
                'hazards' => [
                    ['slug' => 'isolation', 'why' => 'Prolonged isolation and confinement can affect mood, sleep, and cognitive performance.'],
                    ['slug' => 'distance', 'why' => 'Communication delay increases the need to self-monitor behavioural and cognitive signals.'],
                ],
                'guidance' => [
                    'nominal' => ['steps' => ['Keep your regular sleep and check-in routine.'], 'flags' => ['New confusion, severe headache, or vision change.'], 'escalate' => 'Crew medical officer'],
                    'watch' => ['steps' => ['Log your sleep and mood consistently so trends can be seen.'], 'flags' => ['Worsening fatigue despite rest.'], 'escalate' => 'Crew medical officer'],
                    'review' => ['steps' => ['Review your sleep and fatigue trend with the data table.', 'Consider an earlier bedtime and reduced workload today.', 'Repeat your check-in tomorrow.'], 'flags' => ['Fatigue affecting safety-critical tasks.', 'Mood decline lasting several days.'], 'escalate' => 'Crew medical officer'],
                    'urgent' => ['steps' => ['Pause non-essential tasks.', 'Complete a focused check-in now.'], 'flags' => ['Sudden confusion, severe persistent headache, loss of coordination.'], 'escalate' => 'Crew medical officer immediately; Earth team when link available'],
                ],
            ],
            [
                'slug' => 'eyes',
                'name' => 'Eyes',
                'body_system' => 'sensory',
                'mesh_name' => 'Eyes',
                'svg_id' => 'region-eyes',
                'function_text' => 'Vision can be affected by fluid shifts in microgravity and by radiation exposure over time.',
                'sort_order' => 2,
                'metrics' => [
                    ['hazard_metric_code' => 'radiation_dose'],
                ],
                'hazards' => [
                    ['slug' => 'radiation', 'why' => 'Cumulative radiation exposure is a recognised long-duration spaceflight risk to eye health.'],
                    ['slug' => 'gravity', 'why' => 'Microgravity-associated fluid shifts can change vision over a mission.'],
                ],
                'guidance' => [
                    'nominal' => ['steps' => ['No action needed; continue routine vision self-checks.'], 'flags' => ['Sudden blurred or double vision.'], 'escalate' => 'Crew medical officer'],
                    'watch' => ['steps' => ['Note any visual changes in your daily check-in.'], 'flags' => ['Persistent blurriness.'], 'escalate' => 'Crew medical officer'],
                    'review' => ['steps' => ['Record specific visual symptoms (blurring, halos, pain).', 'Reduce screen time if eyes feel strained.'], 'flags' => ['Progressive vision loss.'], 'escalate' => 'Crew medical officer'],
                    'urgent' => ['steps' => ['Stop tasks requiring fine visual judgment.'], 'flags' => ['Sudden vision loss or severe eye pain.'], 'escalate' => 'Crew medical officer immediately; Earth team when link available'],
                ],
            ],
            [
                'slug' => 'heart',
                'name' => 'Heart',
                'body_system' => 'cardiovascular',
                'mesh_name' => 'Heart',
                'svg_id' => 'region-heart',
                'function_text' => 'Circulates blood and adapts to microgravity-driven fluid shifts; resting heart rate trend is a useful personal signal.',
                'sort_order' => 3,
                'metrics' => [
                    ['metric_definition_id' => $hr],
                ],
                'hazards' => [
                    ['slug' => 'gravity', 'why' => 'Microgravity causes cardiovascular deconditioning over time.'],
                ],
                'guidance' => [
                    'nominal' => ['steps' => ['Continue your scheduled exercise countermeasures.'], 'flags' => ['Chest pain, palpitations, fainting.'], 'escalate' => 'Crew medical officer'],
                    'watch' => ['steps' => ['Take a manual resting heart rate reading today.'], 'flags' => ['Resting HR climbing day over day.'], 'escalate' => 'Crew medical officer'],
                    'review' => ['steps' => ['Compare today\'s resting HR with your personal baseline.', 'Avoid intense exertion until re-checked.', 'Re-measure resting HR in a few hours.'], 'flags' => ['HR remains elevated at rest with symptoms.'], 'escalate' => 'Crew medical officer'],
                    'urgent' => ['steps' => ['Stop physical activity.', 'Re-measure heart rate immediately.'], 'flags' => ['Chest pain, severe palpitations, fainting, shortness of breath.'], 'escalate' => 'Crew medical officer immediately; Earth team when link available'],
                ],
            ],
            [
                'slug' => 'lungs',
                'name' => 'Lungs',
                'body_system' => 'respiratory',
                'mesh_name' => 'Lungs',
                'svg_id' => 'region-lungs',
                'function_text' => 'Gas exchange can be affected by cabin air quality, including CO2 build-up in closed environments.',
                'sort_order' => 4,
                'metrics' => [
                    ['hazard_metric_code' => 'cabin_co2'],
                ],
                'hazards' => [
                    ['slug' => 'environment', 'why' => 'Closed-loop life support can allow CO2 or contaminants to build up if systems are stressed.'],
                ],
                'guidance' => [
                    'nominal' => ['steps' => ['No action needed; cabin air quality is within the expected range.'], 'flags' => ['Shortness of breath at rest.'], 'escalate' => 'Crew medical officer'],
                    'watch' => ['steps' => ['Check cabin CO2 reading and ventilation status.'], 'flags' => ['Headache with stuffy air.'], 'escalate' => 'Crew medical officer'],
                    'review' => ['steps' => ['Check environmental control system status.', 'Increase ventilation/scrubbing if possible per protocol.', 'Note any headache or breathlessness in your check-in.'], 'flags' => ['Breathing difficulty for more than one crew member.'], 'escalate' => 'Crew medical officer'],
                    'urgent' => ['steps' => ['Follow onboard emergency air-quality protocol now.'], 'flags' => ['Severe shortness of breath, confusion, multiple crew affected.'], 'escalate' => 'Crew medical officer immediately; Earth team when link available'],
                ],
            ],
            [
                'slug' => 'spine',
                'name' => 'Spine',
                'body_system' => 'musculoskeletal',
                'mesh_name' => 'Spine',
                'svg_id' => 'region-spine',
                'function_text' => 'Spinal elongation and back discomfort are common in microgravity as the spine decompresses.',
                'sort_order' => 5,
                'metrics' => [
                    ['hazard_metric_code' => 'physical_deconditioning'],
                ],
                'hazards' => [
                    ['slug' => 'gravity', 'why' => 'Microgravity causes spinal elongation and is linked to back discomfort.'],
                ],
                'guidance' => [
                    'nominal' => ['steps' => ['Continue resistive exercise as scheduled.'], 'flags' => ['Sudden severe back pain or numbness.'], 'escalate' => 'Crew medical officer'],
                    'watch' => ['steps' => ['Log back comfort in your daily check-in.'], 'flags' => ['Persistent discomfort.'], 'escalate' => 'Crew medical officer'],
                    'review' => ['steps' => ['Complete planned resistive exercise sessions.', 'Use recommended stretching/posture routine.', 'Log symptom severity daily.'], 'flags' => ['Pain radiating to limbs, numbness, weakness.'], 'escalate' => 'Crew medical officer'],
                    'urgent' => ['steps' => ['Stop load-bearing tasks.'], 'flags' => ['Numbness, weakness, loss of bladder/bowel control.'], 'escalate' => 'Crew medical officer immediately; Earth team when link available'],
                ],
            ],
            [
                'slug' => 'upper-body-muscles',
                'name' => 'Upper-body Muscles',
                'body_system' => 'musculoskeletal',
                'mesh_name' => 'UpperBodyMuscles',
                'svg_id' => 'region-upper-muscles',
                'function_text' => 'Upper-body strength is maintained through resistive exercise; disuse leads to gradual muscle loss.',
                'sort_order' => 6,
                'metrics' => [
                    ['hazard_metric_code' => 'physical_deconditioning'],
                ],
                'hazards' => [
                    ['slug' => 'gravity', 'why' => 'Without load-bearing, upper-body muscle mass and strength decline over a mission.'],
                ],
                'guidance' => [
                    'nominal' => ['steps' => ['Keep up planned resistive training.'], 'flags' => ['Sudden severe weakness.'], 'escalate' => 'Crew medical officer'],
                    'watch' => ['steps' => ['Check your exercise completion rate this week.'], 'flags' => ['Repeated missed sessions.'], 'escalate' => 'Crew medical officer'],
                    'review' => ['steps' => ['Catch up on missed resistive-exercise sessions where possible.', 'Note any new weakness or soreness.'], 'flags' => ['Progressive weakness affecting tasks.'], 'escalate' => 'Crew medical officer'],
                    'urgent' => ['steps' => ['Stop manual tasks requiring strength.'], 'flags' => ['Sudden, severe, or one-sided weakness.'], 'escalate' => 'Crew medical officer immediately; Earth team when link available'],
                ],
            ],
            [
                'slug' => 'lower-body-muscles',
                'name' => 'Lower-body Muscles',
                'body_system' => 'musculoskeletal',
                'mesh_name' => 'LowerBodyMuscles',
                'svg_id' => 'region-lower-muscles',
                'function_text' => 'Leg muscles atrophy fastest in microgravity without regular loaded exercise.',
                'sort_order' => 7,
                'metrics' => [
                    ['hazard_metric_code' => 'physical_deconditioning'],
                ],
                'hazards' => [
                    ['slug' => 'gravity', 'why' => 'Leg muscles lose mass quickly without gravity-loaded activity.'],
                ],
                'guidance' => [
                    'nominal' => ['steps' => ['Continue scheduled aerobic and resistive exercise.'], 'flags' => ['Sudden severe leg weakness.'], 'escalate' => 'Crew medical officer'],
                    'watch' => ['steps' => ['Review your exercise completion log.'], 'flags' => ['Declining completion over several days.'], 'escalate' => 'Crew medical officer'],
                    'review' => ['steps' => ['Prioritise lower-body resistive sessions this week.', 'Log balance or gait changes.'], 'flags' => ['Difficulty with balance or standing.'], 'escalate' => 'Crew medical officer'],
                    'urgent' => ['steps' => ['Avoid tasks requiring balance or heavy exertion.'], 'flags' => ['Inability to bear weight, sudden severe weakness.'], 'escalate' => 'Crew medical officer immediately; Earth team when link available'],
                ],
            ],
            [
                'slug' => 'long-bones',
                'name' => 'Long Bones (Hips & Legs)',
                'body_system' => 'musculoskeletal',
                'mesh_name' => 'LongBones',
                'svg_id' => 'region-long-bones',
                'function_text' => 'Weight-bearing bones lose density gradually in microgravity; this is a slow, cumulative process tracked over the whole mission.',
                'sort_order' => 8,
                'metrics' => [
                    ['hazard_metric_code' => 'physical_deconditioning'],
                ],
                'hazards' => [
                    ['slug' => 'gravity', 'why' => 'Bone density loss (particularly hips and legs) is a well-documented long-duration spaceflight risk.'],
                ],
                'guidance' => [
                    'nominal' => ['steps' => ['Keep up resistive exercise; bone health responds slowly over the whole mission.'], 'flags' => ['Unexplained fracture or severe bone pain.'], 'escalate' => 'Crew medical officer'],
                    'watch' => ['steps' => ['Review exercise compliance for the countermeasure program.'], 'flags' => ['Sustained low compliance.'], 'escalate' => 'Crew medical officer'],
                    'review' => ['steps' => ['Increase resistive-exercise compliance toward the planned target.', 'Report any unusual joint or bone pain.'], 'flags' => ['Unusual pain without an injury event.'], 'escalate' => 'Crew medical officer'],
                    'urgent' => ['steps' => ['Avoid weight-bearing on the affected area.'], 'flags' => ['Suspected fracture, severe localized pain after minimal impact.'], 'escalate' => 'Crew medical officer immediately; Earth team when link available'],
                ],
            ],
            [
                'slug' => 'digestive',
                'name' => 'Digestive System',
                'body_system' => 'digestive',
                'mesh_name' => 'Digestive',
                'svg_id' => 'region-digestive',
                'function_text' => 'Diet, stress, and reduced activity in a closed environment can all affect digestion.',
                'sort_order' => 9,
                'metrics' => [
                    ['hazard_metric_code' => 'wellbeing_mood'],
                ],
                'hazards' => [
                    ['slug' => 'environment', 'why' => 'Closed-environment diet and routine changes can affect digestive comfort.'],
                    ['slug' => 'isolation', 'why' => 'Stress from isolation/confinement can affect appetite and digestion.'],
                ],
                'guidance' => [
                    'nominal' => ['steps' => ['Maintain your regular diet and hydration routine.'], 'flags' => ['Severe abdominal pain, persistent vomiting, blood in stool.'], 'escalate' => 'Crew medical officer'],
                    'watch' => ['steps' => ['Note any digestive symptoms in your check-in.'], 'flags' => ['Symptoms lasting more than a day.'], 'escalate' => 'Crew medical officer'],
                    'review' => ['steps' => ['Track specific symptoms (nausea, bloating, pain location).', 'Adjust diet/hydration per onboard guidance.'], 'flags' => ['Worsening or spreading pain, fever.'], 'escalate' => 'Crew medical officer'],
                    'urgent' => ['steps' => ['Stop eating solid food until assessed per protocol.'], 'flags' => ['Severe pain, persistent vomiting, blood in vomit/stool, fever.'], 'escalate' => 'Crew medical officer immediately; Earth team when link available'],
                ],
            ],
            [
                'slug' => 'skin',
                'name' => 'Skin',
                'body_system' => 'skin',
                'mesh_name' => 'Skin',
                'svg_id' => 'region-skin',
                'function_text' => 'The skin barrier can be affected by cabin humidity, hygiene constraints, and minor irritations in a closed environment.',
                'sort_order' => 10,
                'metrics' => [
                    ['hazard_metric_code' => 'cabin_co2'],
                ],
                'hazards' => [
                    ['slug' => 'environment', 'why' => 'Closed, recycled-air environments can contribute to skin dryness or irritation.'],
                ],
                'guidance' => [
                    'nominal' => ['steps' => ['Continue routine hygiene and skin checks.'], 'flags' => ['Spreading rash with fever.'], 'escalate' => 'Crew medical officer'],
                    'watch' => ['steps' => ['Note any irritation or dryness in your check-in.'], 'flags' => ['Irritation not improving.'], 'escalate' => 'Crew medical officer'],
                    'review' => ['steps' => ['Photograph/describe the area and track changes daily.', 'Use onboard skin-care protocol.'], 'flags' => ['Spreading redness, swelling, discharge, fever.'], 'escalate' => 'Crew medical officer'],
                    'urgent' => ['steps' => ['Isolate the area from further irritation.'], 'flags' => ['Rapid spreading, signs of infection, fever.'], 'escalate' => 'Crew medical officer immediately; Earth team when link available'],
                ],
            ],
            [
                'slug' => 'whole-body-radiation',
                'name' => 'Whole Body / Radiation Exposure',
                'body_system' => 'endocrine_general',
                'mesh_name' => 'WholeBody',
                'svg_id' => 'region-whole-body',
                'function_text' => 'Cumulative radiation exposure is tracked across the whole body and whole mission, affecting multiple systems over time.',
                'sort_order' => 11,
                'metrics' => [
                    ['hazard_metric_code' => 'radiation_dose'],
                ],
                'hazards' => [
                    ['slug' => 'radiation', 'why' => 'Cumulative dose across the mission is the primary radiation health consideration.'],
                ],
                'guidance' => [
                    'nominal' => ['steps' => ['No action needed; continue routine dosimeter checks.'], 'flags' => ['Known acute radiation event with symptoms (nausea, fatigue, skin reaction).'], 'escalate' => 'Crew medical officer'],
                    'watch' => ['steps' => ['Review your cumulative dose trend.'], 'flags' => ['Unexplained rise in dose rate.'], 'escalate' => 'Crew medical officer'],
                    'review' => ['steps' => ['Review dose-rate trend against your baseline.', 'Follow onboard shielding/shelter protocol if advised.'], 'flags' => ['Rapid dose-rate rise, possible solar particle event.'], 'escalate' => 'Crew medical officer'],
                    'urgent' => ['steps' => ['Move to designated shelter per onboard radiation protocol immediately.'], 'flags' => ['Confirmed solar particle event, acute radiation symptoms.'], 'escalate' => 'Crew medical officer immediately; Earth team when link available'],
                ],
            ],
        ];

        foreach ($regions as $data) {
            $region = BodyRegion::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'body_system' => $data['body_system'],
                    'mesh_name' => $data['mesh_name'],
                    'svg_id' => $data['svg_id'],
                    'function_text' => $data['function_text'],
                    'sort_order' => $data['sort_order'],
                ]
            );

            $region->metrics()->delete();
            foreach ($data['metrics'] as $i => $m) {
                $region->metrics()->create($m + ['sort_order' => $i]);
            }

            $region->hazards()->delete();
            foreach ($data['hazards'] as $i => $h) {
                $hazardId = $hazardIds[$h['slug']] ?? null;
                if (! $hazardId) {
                    continue;
                }
                $region->hazards()->create([
                    'hazard_id' => $hazardId,
                    'why_it_matters' => $h['why'],
                    'sort_order' => $i,
                ]);
            }

            $region->guidance()->delete();
            foreach ($data['guidance'] as $level => $g) {
                $region->guidance()->create([
                    'status_level' => $level,
                    'self_care_steps' => $g['steps'],
                    'red_flags' => $g['flags'],
                    'escalate_to' => $g['escalate'],
                    'sources' => $g['sources'] ?? [],
                ]);
            }
        }
    }
}
