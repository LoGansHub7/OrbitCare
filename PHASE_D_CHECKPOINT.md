# Phase D Checkpoint: Hazard-Linked Alerts

**Status:** complete, tests passing (`php artisan test`: 34 passed)

## What was added
- `alert_rules.hazard_id` (nullable FK to `hazards`); `AlertRule::hazard()`, `Hazard::alertRules()`.
- Existing rules mapped to hazards: `sleep_fatigue_review` -> Isolation, `resting_heart_rate_change` -> Gravity.
- `App\Services\HazardRuleEvaluator`: five experimental rules over the Phase C monitoring tables.
  Thresholds live in `alert_rules.parameters` (configurable, versioned `v1`) and are team assumptions, not NASA limits.

| Rule | Hazard | Triggers when |
|------|--------|---------------|
| `radiation_dose_rate_rise` | Radiation | last 3 dose rates >= 1.5x the earlier baseline (priority at 2.5x) |
| `wellbeing_decline_trend` | Isolation | mood falls at every one of the last 3 steps (priority if mood <= 2) |
| `autonomy_escalation_under_delay` | Distance | >= 50% of 3+ events with >= 600 s delay were escalated to Earth |
| `physical_deconditioning_trend` | Gravity | muscle index down >= 2% over 4 readings while exercise averages < 60 min |
| `cabin_co2_elevated` | Environment | last 3 cabin CO2 readings all >= 3000 ppm (priority at 5000 avg) |

- `AlertEvaluationService` delegates these rules and adds `hazardStatuses()`:
  priority_review > review > insufficient_data > no_flag. Open alerts keep a hazard flagged after values recover.
- `/hazards` shows real status and alert counts; `/alerts?hazard=<slug>` filters by hazard.
- Insufficient/stale data returns `insufficient_data`, never "no flag". Evidence snapshots record `includes_simulated`.

## Fixes
- Phase B view linked to an undefined `hazards.show` route; it now links to each hazard's monitor page and filtered alerts.
- The colleagues' main dashboard ignores hazard rules in `currentStates()` by default, so its behaviour is unchanged.

## Next
Run `php artisan migrate` (and `db:seed --class=AlertRuleSeeder`) once MySQL is back, then continue with the next phase.
