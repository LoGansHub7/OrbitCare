# Integration Plan: Your Roadmap + Colleagues' System

**Goal:** Add your NASA five-hazards framework (Phases A-H) to colleagues' working Phases 0-9 system without breaking anything.

---

## Architecture Mapping

### Their System (Live)
```
MetricDefinition → HealthReading → AlertRule evaluation
        ↓
AlertEvaluationService (evidence-based)
        ↓
Alert + AlertEvidence (explainability)
```

### Your System (To Integrate)
```
Hazard → Risk + Reference (domain structure)
   ↓
Mission Profile (LEO/Moon/Mars weighting)
   ↓
Monitoring Tables (Radiation, Wellbeing, Autonomy, Physical, Environment)
   ↓
AlertRule evaluation (using hazard + metric)
```

---

## Integration Strategy

### Phase 1: Add Hazard Framework (no breaking changes)
1. Create `Hazard`, `Risk`, `Reference` models + migrations
2. Create `HazardSeeder` with 5 NASA hazards, 20+ risks, 8+ references
3. Create `MissionProfile` migration (add hazard_weightings to `missions` table)
4. **NO changes needed to their code** - these are additive

### Phase 2: Link Hazards to Metrics
- Each `MetricDefinition` gets a `hazard_id` foreign key (nullable, optional)
- Example: "radiation_dose" → links to Hazard::where('slug','radiation')
- "sleep_quality" → links to Hazard::where('slug','isolation')
- This enriches their metrics with hazard context

### Phase 3: Monitoring Pages (Build UI)
- Create dashboard pages per hazard:
  - `/monitor/radiation` → shows `HealthReading` entries for radiation metrics
  - `/monitor/isolation` → shows `DailyCheckin` entries filtered for isolation hazard
  - `/monitor/distance` → autonomy events (new table or repurposed `MissionEvent`)
  - `/monitor/gravity` → physical readings (use `HealthReading` + `SymptomLog`)
  - `/monitor/environment` → cabin data (new table or repurposed `MetricDefinition`)
- All pages use their existing design system + components

### Phase 4: Hazard-Centered Alerts
- `AlertRule` model already exists and is flexible
- Add view: `/alerts` showing current alerts grouped by hazard
- Add "Evaluate alerts" command already implemented in their system (`AlertEvaluationService`)
- Your Phase D alert logic can enhance their evaluation engine

### Phase 5-8: Higher-Level Features
- Risk register, evidence library, simulation mode, reports
- All build on top of the hazard framework

---

## Implementation Checklist

- [ ] Create migrations (Hazard, Risk, Reference tables + mission hazard_weightings)
- [ ] Create models (Hazard, Risk, Reference)
- [ ] Create seeders (HazardSeeder, ReferenceSeeder, migrate 20+ risks)
- [ ] Add hazard_id foreign key to MetricDefinition (nullable, optional)
- [ ] Create HazardDashboardController + views
- [ ] Create 5 monitoring pages (one per hazard)
- [ ] Integrate with their AlertRule and AlertEvaluationService
- [ ] Update navigation to include hazard links
- [ ] Test: `php artisan migrate:fresh --seed`
- [ ] Test: all 5 hazard pages load
- [ ] Test: alerts are properly categorized by hazard

---

## Files to Create/Modify

### New Files (Your Roadmap)
```
database/migrations/2026_11_14_XXXXXX_create_hazards_table.php
database/migrations/2026_11_14_XXXXXX_create_risks_table.php
database/migrations/2026_11_14_XXXXXX_create_references_table.php
database/migrations/2026_11_14_XXXXXX_add_hazard_weightings_to_missions.php
database/seeders/HazardSeeder.php
database/seeders/ReferenceSeeder.php
database/seeders/RiskSeeder.php
app/Models/Hazard.php
app/Models/Risk.php
app/Models/Reference.php
app/Http/Controllers/HazardMonitorController.php
app/Http/Controllers/HazardDashboardController.php
resources/views/hazards/index.blade.php
resources/views/hazards/radiation.blade.php
resources/views/hazards/isolation.blade.php
resources/views/hazards/distance.blade.php
resources/views/hazards/gravity.blade.php
resources/views/hazards/environment.blade.php
```

### Modified Files (Minimal Changes)
```
database/migrations/XXXX_XX_XX_XXXXXX_create_metric_definitions_table.php
  → Add: $table->foreignId('hazard_id')->nullable()->constrained('hazards')->nullOnDelete();

app/Models/MetricDefinition.php
  → Add: public function hazard() { return $this->belongsTo(Hazard::class); }

routes/web.php
  → Add: Route group for /monitor/* routes

database/seeders/DatabaseSeeder.php
  → Call: HazardSeeder, ReferenceSeeder, RiskSeeder
```

---

## Risk Mitigation

### Keep Their System Intact
- ✅ No changes to `HealthReading` table structure
- ✅ No changes to `CrewProfile`, `Mission`, `DailyCheckin`
- ✅ No changes to `AlertRule` evaluation logic
- ✅ No changes to their Blade views

### Data Honesty
- ✅ All seeded data uses real NASA/CSA URLs
- ✅ All thresholds marked as illustrative
- ✅ Simulated data labeled on UI

### Testing
- ✅ New migrations must not fail on existing data
- ✅ Existing tests must still pass
- ✅ No breaking changes to their models/controllers

---

## Timeline (No Database = Code-Only for Now)

1. Create 4 migrations + 3 seeders (~30 min)
2. Create 3 models (~15 min)
3. Create controllers + dashboard view (~45 min)
4. Create 5 monitoring pages (~60 min)
5. Wire up navigation + routes (~30 min)
6. Test + commit (~30 min)

**Total: ~3 hours of development**

**Blocker:** Can't run `php artisan migrate` without MySQL, so we'll do:
1. Create all files
2. Review code syntax
3. Commit to git
4. When MySQL is ready: `php artisan migrate:fresh --seed` → verify

---

## Success Criteria

✅ All 5 hazards appear in the dashboard  
✅ Each hazard has a monitoring page  
✅ Pages show real readings from `HealthReading` + `DailyCheckin`  
✅ References link to real NASA/CSA sources  
✅ Thresholds clearly marked "illustrative"  
✅ Existing tests pass  
✅ Git history is clean (one commit per phase)  

---

**Ready to start?** Confirm MySQL status, then I'll implement Phase A first.
