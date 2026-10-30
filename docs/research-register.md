# OrbitCare research register and data provenance

**Phase:** 2  
**Reviewed:** 2026-10-30  
**Scope:** The sources below explain why OrbitCare tracks its chosen indicators. They do **not** validate a diagnosis, a clinical decision, or any alert threshold in this prototype.

## Research register

| Source title | Publisher | URL | Indicator supported | Relevant finding | Application use | Limitation |
| --- | --- | --- | --- | --- | --- | --- |
| Risk from Inadequate Sleep and Irregular Schedules | NASA Human Research Program | https://www.nasa.gov/reference/risk-from-inadequate-sleep-and-irregular-schedules | Sleep duration, sleep quality, fatigue | NASA identifies sleep loss, circadian desynchronisation, and work overload as risks to crew health and performance. | Supports collecting daily sleep duration/quality and fatigue alongside mission context. | It is a risk summary, not a patient-specific prediction or a rule for interpreting an individual’s records. |
| Science in Space: Week of Sept. 15, 2023 — Sleep on Station | NASA | https://www.nasa.gov/humans-in-space/science-in-space-week-of-sept-15-2023-sleep-on-station | Sleep duration, sleep quality, fatigue | NASA reports that circadian-misaligned sleep episodes on station were associated with less sleep and poorer reported sleep quality; it also describes studies relating shorter sessions to tiredness and performance measures. | Supports the dashboard’s sleep and fatigue trend views and the later experimental sleep/fatigue rule family. | This does not establish a diagnostic relationship and does not prescribe the prototype’s sample count, cut-off, or alert severity. |
| NASA-STD-3001 Technical Brief — Sleep Accommodations | NASA Office of the Chief Health and Medical Officer | https://www.nasa.gov/wp-content/uploads/2023/03/sleep-accomodations-technical-brief-ochmo.pdf | Sleep duration | The technical brief reports average nightly sleep duration of roughly 6–6.5 hours for Shuttle and ISS in the cited reference data. | Provides spaceflight context for displaying and discussing sleep duration. | Population averages must not be used as a normal value or threshold for an individual astronaut. |
| Fatigue Countermeasures Laboratory | NASA Ames Research Center | https://www.nasa.gov/human-systems-integration-division/human-performance/fatigue-countermeasures-laboratory/ | Sleep, circadian timing, fatigue | NASA studies how acute/chronic sleep loss and circadian misalignment affect alertness and performance, including in spaceflight and analog settings. | Supports the product’s focus on patterns over time and a clear experimental-status label. | It does not supply production-ready medical alert logic or establish that self-reported fatigue alone identifies a health condition. |
| Exercise Overview (OCHMO-TB-031) | NASA Office of the Chief Health and Medical Officer | https://www.nasa.gov/ochmo-tb-031-exercise-overview-2/ | Exercise duration, heart rate context | NASA describes exercise as an important spaceflight countermeasure and notes cardiovascular factors, including heart rate, in exercise physiology. | Supports recording exercise duration and a separate resting-heart-rate observation. | The overview does not validate a resting-heart-rate baseline or alert threshold for this app. Exercise duration is self-reported in OrbitCare. |

## Candidate data-set assessment

OrbitCare currently imports **no external research dataset**. The demonstration records are deliberately synthetic so that access controls, trends, and later alert explanations can be tested safely and repeatably.

| Candidate/source | Access available? | Data type | Relevant variables/units | Comparable situation? | Reuse decision |
| --- | --- | --- | --- | --- | --- |
| NASA sleep technical brief and NASA web research summaries | Publicly accessible reference material | Published summaries and reference data | Sleep duration, quality/context; units and collection protocols vary by cited study | Relevant spaceflight context, but not an OrbitCare participant dataset | Cite as background only; do not import values or derive thresholds. |
| ISS sleep-shifting record (NTRS 20240002094) | Publicly accessible publication record | Human ISS study | Objective sleep duration from wrist accelerometry; 19 crew members described in the record | Relevant, but limited sample and protocol-specific | Cite only if needed in a presentation; do not ingest individual-level data. |
| OrbitCare `DemoSeeder` | Included in this repository | Fictional synthetic demonstration data | Sleep in hours, fatigue/quality scores (1–5), exercise in minutes, resting HR in bpm; 14 days per demo crew member | Designed for product demonstration, not research comparability | Seed locally with `php artisan migrate:fresh --seed`; never describe it as NASA or clinical data. |

## Data provenance statement

| Information category | OrbitCare status and handling |
| --- | --- |
| Imported research data | **None at present.** No interface value, chart point, baseline, or alert result is imported from NASA or another research dataset. |
| Synthetic demonstration data | The three fictional crew scenarios seeded by `database/seeders/DemoSeeder.php` are synthetic. UI labels must say **Simulation** and documentation/presentations must not represent them as astronaut or NASA measurements. |
| Manually entered data | An authenticated astronaut may submit a daily check-in and a resting-heart-rate reading. These entries are labelled **Manual** and are visible only through authorised flows. |
| Experimental team assumptions | Current plausibility bounds reject clearly invalid input; they are data-quality checks, not medical thresholds. Future alert rules, baselines, evaluation windows, severities, and labels are experimental assumptions until separately documented and validated. |

## Required interface language

- Do not call a result “healthy”, “normal”, diagnostic, or medically validated.
- `no_flag` means **no implemented rule triggered**, not that a person is medically healthy.
- `insufficient_data` means the application cannot evaluate its implemented rule, not that no risk exists.
- Research citations should be shown with their direct links and an explicit limitation near any product claim they inform.
