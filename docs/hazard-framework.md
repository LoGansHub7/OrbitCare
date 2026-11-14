# Hazard framework (NASA five hazards)

OrbitCare organises monitoring around NASA's five hazards of human spaceflight: **Radiation, Isolation, Distance, Gravity, Environment**. This layer sits on top of the metric/alert engine.

All thresholds are **team assumptions** stored in `alert_rules.parameters` (versioned, labelled experimental), not NASA limits. NASA/CSA sources provide context only. Simulated rows carry `is_simulated = true` and are labelled in the UI, reports and CSV.

## Pages

| Page | Route | Roles |
|------|-------|-------|
| Five-hazards dashboard (status, alert count, what-if mission weighting via `?profile=LEO\|Moon\|Mars`) | `/hazards` | astronaut |
| Hazard monitors (radiation, wellbeing, autonomy, physical, environment) | `/monitor/*` | astronaut |
| Alerts filtered by hazard | `/alerts?hazard=<slug>` | astronaut |
| Risk register | `/risks` | any signed-in role |
| Evidence library | `/evidence` | any signed-in role |
| Hazard report (print) and CSV | `/reports/hazards`, `/exports/my-hazard-data.csv` | astronaut |
| Assigned crew hazard report and CSV | `/staff/crew/{id}/hazards`, `/staff/crew/{id}/hazard-data.csv` | assigned medical officer |

Admins have no access to hazard health data.

## Alert rules (`App\Services\HazardRuleEvaluator`)

| Rule | Hazard | Triggers when |
|------|--------|---------------|
| `radiation_dose_rate_rise` | Radiation | last 3 dose rates >= 1.5x earlier baseline (priority at 2.5x) |
| `wellbeing_decline_trend` | Isolation | mood falls at every one of the last 3 steps |
| `autonomy_escalation_under_delay` | Distance | >= 50% of 3+ events with >= 600 s delay escalated to Earth |
| `physical_deconditioning_trend` | Gravity | muscle index down >= 2% over 4 readings with < 60 min/day exercise |
| `cabin_co2_elevated` | Environment | last 3 cabin CO2 readings all >= 3000 ppm |

Missing or stale data gives `insufficient_data`; an open alert is never cleared just because numbers recover.

## Simulation

`App\Services\HazardSimulator` generates deterministic fictional data. Scenarios: `stable`, `radiation_event`, `isolation_decline`, `comms_delay`, `deconditioning`, `cabin_co2`.

```bash
# requires ORBITCARE_DEMO_MODE=true (never in production)
php artisan orbitcare:simulate radiation_event --crew=OC-01
```

The admin demo page exposes the same scenarios. Cabin readings are mission-level, so applying a scenario replaces the mission's simulated cabin rows. `php artisan migrate:fresh --seed` seeds OC-01 as stable and OC-02 as an isolation decline; OC-03 has no hazard data.
