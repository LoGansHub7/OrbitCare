**OrbitCare should be a responsive Laravel + MySQL web application that helps astronauts record health indicators, review trends, understand experimental alerts, and document follow-up actions.**

The plan below contains **22 development phases**. Each phase has a clear output and completion condition so you can assign several phases to each teammate.

**MySQL must run during development as well as presentation.** Develop against local MySQL databases, then deploy the Laravel application and a separate presentation database later.

One important planning point: use this roadmap for preparation, but confirm the event’s rules on advance project development before building the actual competition submission.

---

## Project foundation

### Agreed technology stack

| Area | Decision |
|---|---|
| Project name | **OrbitCare** |
| Application type | Responsive web application |
| Backend | Laravel |
| Frontend | Blade templates and Bootstrap 5 |
| Small interactive behaviours | Plain JavaScript |
| Charts | Chart.js |
| Database | MySQL |
| Authentication | Laravel session authentication |
| Source control | Git and a shared GitHub repository |
| Development tools | VS Code, Composer, Node.js/npm, PHP, MySQL |
| Deployment | Hosting that supports your chosen Laravel/PHP version and MySQL |

Use one Laravel version and one supported PHP version across the team. Commit `composer.lock` and `package-lock.json` so everyone installs the same dependencies.

**Keep the initial application as one Laravel project.** A separate React frontend, mobile app, or Python service would add integration work that this prototype does not require.

### Main user roles

| Role | Purpose | Access |
|---|---|---|
| Astronaut | Record and understand personal health information | Own readings, check-ins, alerts, and reports |
| Crew medical officer | Review assigned crew members and follow-ups | Only crew explicitly assigned to them |
| Administrator | Manage accounts, assignments, and configuration | No automatic access to private health records |

For a small prototype, an account has one role. You can seed separate accounts for demonstrations.

### Core project boundary

Build around:

- Sleep duration and quality.
- Self-reported fatigue.
- Resting heart rate.
- Exercise duration.
- Optional symptom notes.
- Explainable experimental alerts.
- Follow-up records.

Do not claim that these indicators diagnose illness, measure bone loss, or establish radiation exposure.

---

## Phase 1 — Define the problem and success criteria

**Purpose:** Make sure every teammate is building the same product.

**Create:**

A short project specification containing:

1. **Problem statement:** Astronauts need a clear way to notice changes in personal health indicators and record follow-up during missions.
2. **Primary user:** An astronaut completing a daily health review.
3. **Supporting user:** A crew medical officer reviewing assigned crew.
4. **Main workflow:** Record → review → understand alert → follow up.
5. **Prototype limitations:** Synthetic demonstration data, experimental rules, no diagnosis.
6. **Feature priorities.**

Use these priorities:

| Priority | Features |
|---|---|
| Essential | Login, daily check-in, readings, dashboard, trends, alerts, follow-ups |
| Supporting | Crew overview, CSV import, reports, research references |
| Optional | Guided scenario playback, additional visual polish |
| Future | Wearables, browser offline editing, synchronisation, validated predictive models |

**Completion condition:** Everyone can explain the same main user journey and identify what is outside the build.

**Depends on:** Nothing.

---

## Phase 2 — Research NASA sources and document evidence

**Purpose:** Give the project a credible connection to space health.

**Create:**

A research register with these columns:

| Field | What to record |
|---|---|
| Source title | Exact study, dataset, or publication title |
| Publisher | NASA or other identified organisation |
| URL | Direct source |
| Indicator supported | Sleep, fatigue, activity, heart rate, etc. |
| Relevant finding | A careful summary |
| Application use | Which feature it informs |
| Limitation | What the source does not establish |

For each candidate dataset, check:

- Is access actually available?
- Is it human, animal, cellular, or environmental data?
- Are the relevant variables present?
- What units and collection intervals are used?
- Does it describe a comparable situation?
- What reuse conditions apply?

**Create a data provenance statement** explaining which information is:

- Imported research data.
- Synthetic demonstration data.
- Manually entered.
- An experimental assumption made by your team.

NASA research can justify your choice of indicators without validating your alert thresholds.

**Completion condition:** Every scientific claim in the interface or presentation has an identified source or is clearly marked as an assumption.

**Depends on:** Phase 1.

---

## Phase 3 — Establish team working rules and interfaces

**Purpose:** Prevent six independently built sections from conflicting.

**Create:**

- One shared repository.
- A task board with owners and dependencies.
- A branch naming convention, such as `feature/daily-checkin`.
- A shared installation guide.
- A route list.
- A database naming agreement.
- A shared list of statuses, metric codes, and units.
- A list of owners for commonly edited files.

Agree on these conventions:

| Item | Convention |
|---|---|
| Tables and columns | `snake_case` |
| PHP classes | `PascalCase` |
| Metric codes | `resting_heart_rate`, `sleep_duration`, `fatigue_rating` |
| Stored timestamps | UTC |
| Displayed times | Clearly labelled timezone |
| Dates sent by forms/imports | Documented, consistent format |
| Health statuses | `no_flag`, `review`, `priority_review`, `insufficient_data` |
| Reading sources | `manual`, `csv_import`, `simulation` |

**Important:** “No flag” means no implemented rule triggered. It must not mean “medically healthy.”

Assign one integration owner to review changes to routes, shared layouts, migrations, and dependencies.

**Completion condition:** Every teammate can install the project and knows the agreed names used by other modules.

**Depends on:** Phase 1.

---

## Phase 4 — Define the OrbitCare visual design

**Purpose:** Make the product feel coherent and professional.

Use a **dark navigation sidebar with a light clinical workspace**. This gives a space-themed identity while keeping charts and forms readable.

### Colour palette

| Purpose | Colour | Hex |
|---|---|---|
| Sidebar and dark headings | Deep navy | `#0B1220` |
| Primary buttons and links | Blue | `#2563EB` |
| Secondary accent | Teal | `#0F766E` |
| Main background | Pale slate | `#F8FAFC` |
| Cards and forms | White | `#FFFFFF` |
| Main text | Dark slate | `#0F172A` |
| Secondary text | Slate | `#475569` |
| Borders | Light slate | `#E2E8F0` |
| No-flag indicator | Green | `#15803D` |
| Review indicator | Amber | `#B45309` |
| Priority-review indicator | Red | `#B91C1C` |
| Unknown/stale indicator | Grey | `#64748B` |

Use text labels and icons alongside colours. A user should understand an alert without distinguishing red from green.

### Typography and spacing

- Font: **Inter**, bundled locally, with system-font fallbacks.
- Body text: 16 px.
- Secondary labels: 14 px.
- Page headings: approximately 28–32 px.
- Metric values: approximately 30–36 px.
- Spacing scale: 4, 8, 12, 16, 24, and 32 px.
- Card corners: approximately 12 px.
- Card padding: approximately 20–24 px.
- Buttons: approximately 44 px high.
- Use subtle borders and restrained shadows.

### Styling rules

- Use one icon library, preferably Bootstrap Icons.
- Use sentence case for labels.
- Keep animations brief and functional.
- Avoid glowing panels, excessive gradients, and decorative moving backgrounds.
- Do not place text over busy space imagery.
- Keep health information visually dominant.

**Create:** A design reference showing buttons, cards, badges, form fields, navigation, and chart colours.

**Completion condition:** All developers use the same tokens and components.

**Depends on:** Phase 1.

---

## Phase 5 — Design the screens and user journeys

**Purpose:** Decide layout before implementing individual pages.

**Create wireframes for:**

| Screen | Required content |
|---|---|
| Login | Product identity, credentials, errors |
| My dashboard | Latest indicators, freshness, trends, alerts |
| Daily check-in | Sleep, fatigue, exercise, symptoms |
| My readings | History, filters, manual entry |
| Trends | Date range, charts, baseline explanation |
| Alerts | Filters, explanations, status |
| Alert details | Evidence, rule, acknowledgement, follow-ups |
| Crew overview | Assigned crew and review needs |
| Crew member details | Authorised health summary |
| Reports | Date range and export |
| Research and limitations | Sources, assumptions, data provenance |
| Administration | Accounts and assignments |

### Dashboard layout

**Top bar:** Page title, mission day, user menu.

**First row:** Four cards:

- Resting heart rate.
- Sleep duration.
- Fatigue rating.
- Exercise duration.

Each card shows value, unit, measurement time, source, and freshness.

**Second row:** Trend chart and active-alert panel.

**Third row:** Check-in summary and recent follow-up activity.

On smaller screens, collapse the sidebar into a menu and stack cards vertically.

**Completion condition:** Every page has an agreed purpose, layout, and navigation path.

**Depends on:** Phases 1 and 4.

---

## Phase 6 — Initialise Laravel and local development

**Purpose:** Provide a working shared foundation.

**Create:**

- Laravel application.
- MySQL database named `orbitcare`.
- Environment example.
- Authentication foundation.
- Bootstrap and Chart.js dependencies.
- Base layouts.
- Initial application configuration.
- A health-check route with no sensitive details.

Each developer uses their own local database credentials.

Keep secrets in `.env`; commit only `.env.example` with placeholders.

**Verify:**

- Application starts.
- Laravel connects to MySQL.
- Migrations run.
- Frontend assets build.
- A fresh checkout works using the setup instructions.

If using XAMPP, check the actual database engine: some distributions provide MariaDB. If your agreed target is MySQL, standardise on MySQL across development and deployment.

**Completion condition:** A teammate can clone and run OrbitCare without undocumented steps.

**Depends on:** Phase 3.

---

## Phase 7 — Create the database schema and models

**Purpose:** Establish stable relationships for the remaining modules.

**Create Laravel migrations and Eloquent models for:**

| Table | Main fields |
|---|---|
| `users` | Name, email, password hash, role, active status |
| `missions` | Name, start date, end date, description |
| `crew_profiles` | User, mission, crew code |
| `care_assignments` | Medical officer, crew profile |
| `metric_definitions` | Code, label, canonical unit, input bounds, freshness period |
| `health_readings` | Crew, metric, value, observed time, source, quality flag, import batch |
| `daily_checkins` | Crew, mission date, sleep duration, sleep quality, fatigue, exercise, symptom notes |
| `baseline_profiles` | Crew, metric, baseline value, method, reference period, minimum sample requirement |
| `alert_rules` | Code, version, metric, parameters, severity, explanation, evidence reference |
| `alerts` | Crew, rule version, evidence window, explanation snapshot, status |
| `followups` | Alert, author, action, notes, recorded time |
| `research_sources` | Title, publisher, URL, notes, access date |
| `import_batches` | Uploader, file identifier, source type, result counts, status |
| `audit_events` | Actor, action, affected record, time |

### Database rules

- Use foreign keys.
- Make email and metric codes unique.
- Allow one daily check-in per crew member per mission date.
- Index readings by crew, metric, and observed time.
- Store numeric measurements in appropriate decimal columns.
- Store canonical units consistently.
- Keep daily check-ins authoritative for sleep, fatigue, and exercise; do not duplicate those values into `health_readings`.
- Preserve alert evidence when rules or readings change.
- Do not cascade-delete historical health information when disabling an account.

**Completion condition:** Migrations run from an empty database, relationships work, and realistic sample records can be stored.

**Depends on:** Phases 1, 3, and 6.

---

## Phase 8 — Implement authentication and permissions

**Purpose:** Ensure each user sees only authorised records.

**Create:**

- Login and logout.
- Protected routes.
- Role-based navigation.
- Account activation/deactivation.
- Policies for crew records, readings, alerts, follow-ups, imports, and reports.
- Seeded demonstration accounts.

Use server-side authorisation on every protected operation. Hiding a menu item is not access control. Laravel policies provide a structured way to organise these checks. [laravel.com](https://laravel.com/framework/docs/13.x/authorization?utm_source=chatgpt.com)

### Required permission checks

- Astronaut A cannot view Astronaut B’s records.
- A medical officer sees only assigned crew.
- Administrators manage accounts without automatically receiving health-record access.
- Exports enforce the same permissions as on-screen views.
- Deactivated users cannot continue using protected functions.

**Completion condition:** Directly changing a URL or submitted record ID does not bypass permissions.

**Depends on:** Phases 6 and 7.

---

## Phase 9 — Build reusable interface components

**Purpose:** Give every feature the same appearance and behaviour.

**Create shared components for:**

- Sidebar and mobile navigation.
- Top bar.
- Page heading.
- Metric card.
- Status badge.
- Alert summary.
- Form input and validation error.
- Date filter.
- Table and pagination.
- Empty state.
- Loading state.
- Error state.
- Confirmation dialog.
- Data-source label.
- Freshness indicator.

Use one stylesheet for design tokens and shared styling.

**Create a development-only component preview page** so teammates can inspect available components.

**Completion condition:** New screens can be assembled using shared components without inventing new visual styles.

**Depends on:** Phases 4, 5, and 6.

---

## Phase 10 — Build mission, crew, and assignment management

**Purpose:** Supply the context needed by health features.

**Create:**

- Mission listing and editing.
- Crew profiles.
- Crew-to-mission assignment.
- Medical-officer-to-crew assignment.
- Crew code and mission-day display.
- Account deactivation.

For the prototype, support one active mission per astronaut.

Avoid spending time on profile photographs, document uploads, or extensive personal biographies.

**Completion condition:** An administrator can create the demo mission, assign astronauts, and connect them to a medical officer.

**Depends on:** Phases 7–9.

---

## Phase 11 — Build the daily check-in

**Purpose:** Give astronauts a quick, usable daily workflow.

**Create a form containing:**

- Mission date.
- Sleep duration in hours.
- Sleep quality on a clearly explained scale.
- Fatigue on a clearly explained scale.
- Exercise duration in minutes.
- Optional symptom notes.

Keep the direction of scales explicit—for example, whether a higher fatigue value means worse fatigue.

### Required behaviour

- Prefill the current mission date.
- Show the last completed check-in.
- Prevent duplicate daily submissions.
- Allow corrections with an audit entry.
- Validate values on the server.
- Preserve entered values when validation fails.
- Show a clear save confirmation.

The check-in should take roughly a minute to complete.

**Completion condition:** A user can submit, review, and correct a check-in, and those values appear in the correct history.

**Depends on:** Phases 7–10.

---

## Phase 12 — Build readings entry and CSV import

**Purpose:** Collect numerical measurements without requiring hardware.

**Create:**

1. A manual resting-heart-rate form.
2. A readings history.
3. A CSV template.
4. An import preview.
5. A validation summary.
6. An import result page.

Suggested CSV columns:

```text
crew_code,metric_code,value,unit,observed_at,source
```

### Validation requirements

- Recognised crew and metric.
- Authorised target crew.
- Numeric value.
- Correct canonical unit.
- Valid timestamp.
- Defined duplicate handling.
- File-size and row-count limits.
- Clear distinction between synthetic and research data.

For the prototype, use **validate first, then import the whole batch in a transaction**. If validation fails, show row-specific errors and import nothing.

Input plausibility bounds are data-quality checks, not medical alert thresholds.

**Completion condition:** Valid data imports once; invalid or duplicate data produces a clear explanation without partial corruption.

**Depends on:** Phases 7, 8, and 10.

---

## Phase 13 — Create baselines and experimental alert rules

**Purpose:** Define exactly how the system interprets observations.

**Create two initial rule families:**

1. Sustained sleep reduction accompanied by increased reported fatigue.
2. Sustained change in resting heart rate relative to an individual baseline.

For each rule, document:

- Input indicators.
- Required sample count.
- Reference baseline.
- Evaluation window.
- Missing-data behaviour.
- Trigger condition.
- Severity.
- Explanation template.
- Evidence source or experimental assumption.
- Rule version.

Use a fixed, seeded reference period for the first demonstration. Do not silently recalculate a baseline using the same recent observations being evaluated.

**Avoid a single “health percentage.”** It suggests a level of medical precision the prototype does not establish.

**Completion condition:** The research owner and developer can independently describe why each sample scenario triggers—or does not trigger—a rule.

**Depends on:** Phases 2, 7, 11, and 12.

---

## Phase 14 — Implement the alert engine

**Purpose:** Turn documented rules into reproducible application behaviour.

**Create a dedicated service**, such as `AlertEvaluationService`, responsible for:

- Loading eligible data.
- Checking freshness and sample sufficiency.
- Applying the correct baseline.
- Evaluating rule conditions.
- Saving an evidence snapshot.
- Creating or updating the relevant alert.
- Avoiding duplicate alerts.

Run evaluation after check-in submission, measurement entry, and successful import.

### Important behaviour

- Missing data produces an insufficient-data state.
- An isolated questionable reading does not masquerade as a confirmed trend.
- Repeating the same evaluation does not create duplicate alerts.
- Corrected data triggers reevaluation.
- Existing alert history remains traceable.
- Improved readings do not silently erase an unresolved alert.

**Completion condition:** Replaying the same input produces the same result and a readable explanation.

**Depends on:** Phase 13.

---

## Phase 15 — Build the dashboard and trend views

**Purpose:** Make the collected information understandable.

**Create:**

- Latest-indicator cards.
- Data freshness labels.
- Seven-day and fourteen-day filters.
- Sleep trend.
- Fatigue trend.
- Resting-heart-rate trend.
- Exercise summary.
- Active-alert list.
- Daily check-in reminder.

### Chart rules

- Label axes and units.
- Show measurement timestamps.
- Display missing values as gaps.
- Explain baseline reference lines.
- Do not mix unrelated units on one unexplained axis.
- Provide a table alternative.
- Clearly label simulation data.
- Display “insufficient data” instead of an empty-looking healthy chart.

**Completion condition:** A user can identify what changed, when it changed, and whether the underlying data is current.

**Depends on:** Phases 9, 11, 12, and 14.

---

## Phase 16 — Build alert review and follow-up workflows

**Purpose:** Complete the challenge’s “evaluate and act” journey.

**Create an alert-detail page showing:**

- What changed.
- Which measurements support it.
- Comparison baseline.
- Rule version.
- Experimental status.
- Data-quality limitations.
- Acknowledgement control.
- Follow-up timeline.

Use a simple workflow:

**Open → acknowledged → under review → closed.**

Require a note when closing an alert. Record who changed its status and when.

Follow-up actions can include:

- Rechecked the measurement.
- Completed another check-in.
- Requested medical-officer review.
- Recorded an observation.
- Followed an identified demonstration protocol.

Do not generate medication or treatment instructions.

**Completion condition:** A user can move from an alert to a documented follow-up without losing the original evidence.

**Depends on:** Phases 8, 9, and 14.

---

## Phase 17 — Build the medical-officer crew overview

**Purpose:** Help an authorised reviewer prioritise attention.

**Create:**

- Assigned-crew list.
- Outstanding-alert counts.
- Last check-in time.
- Missing/stale-data indicator.
- Filters by review status.
- Link to each authorised crew member’s detail page.
- Follow-up activity view.

Use a clear table or restrained cards. Prioritise outstanding review needs rather than ranking astronauts by an invented health score.

**Completion condition:** A medical officer can identify who needs review and access the supporting information, while unassigned crew remain inaccessible.

**Depends on:** Phases 10, 15, and 16.

---

## Phase 18 — Build reports and the research page

**Purpose:** Make the project explainable and easy to assess.

**Create:**

### A printable health summary

Include:

- Crew code and mission.
- Selected date range.
- Indicator summaries.
- Data completeness.
- Alerts and follow-ups.
- Data-source labels.
- Generation time.
- Prototype limitations.

Use print styling so the browser can save it as PDF. A dedicated PDF package can wait.

### CSV export

Export authorised readings and check-ins with consistent units and timestamps.

### Research and limitations page

Show:

- NASA references.
- Dataset provenance.
- Synthetic-data explanation.
- Experimental rule assumptions.
- Known limitations.
- Future validation needs.

**Completion condition:** Someone unfamiliar with the project can distinguish evidence, simulation, and assumptions.

**Depends on:** Phases 2, 15, and 16.

---

## Phase 19 — Build repeatable demonstration scenarios

**Purpose:** Make presentations reliable.

**Create three fictional crew scenarios:**

| Scenario | Expected result |
|---|---|
| Stable indicators | No implemented rule triggered |
| Reduced sleep and increasing fatigue | Experimental review alert |
| Missing or stale readings | Insufficient-data state |

Optionally add a fourth scenario showing sustained heart-rate change.

**Create:**

- Deterministic seeders.
- A fixed demonstration clock or documented date anchor.
- A scenario guide.
- A reset procedure.
- Clearly labelled demo accounts.
- Optional scenario playback.

A fixed date anchor matters: records seeded today must not all become stale by presentation day.

Protect reset and simulation controls. They should operate only in a designated demo environment and must never wipe a real database.

**Completion condition:** The team can reset and replay the same demonstration successfully.

**Depends on:** Phases 11–18.

---

## Phase 20 — Integrate, test, and polish

**Purpose:** Verify the complete journey and remove inconsistencies.

**Create automated tests for the consequential behaviour:**

- Authentication and record ownership.
- Medical-officer assignments.
- Duplicate daily check-ins.
- Import validation and transaction rollback.
- Rule boundaries.
- Insufficient samples.
- Missing/stale data.
- Repeated alert evaluation.
- Alert closure and history.
- Export permissions.

**Perform manual checks for:**

- Phone, tablet, and laptop layouts.
- Keyboard navigation.
- Visible focus indicators.
- Readable colour contrast.
- Chart labels and table alternatives.
- Empty and error states.
- Slow requests and repeated clicks.
- Long names and notes.
- Browser printing.
- Unavailable external internet.

Bundle fonts, scripts, icons, and styles locally so the local demonstration does not depend on external CDNs.

**Completion condition:** The main journey works from login through follow-up and reporting, with no known permission failures or presentation-blocking defects.

**Depends on:** All core feature phases.

---

## Phase 21 — Deploy Laravel and MySQL

**Purpose:** Prepare the version judges will access.

**Create:**

- Deployment instructions.
- Production environment configuration.
- Presentation database.
- Release checklist.
- Database backup.
- Rollback procedure.
- Local backup deployment.

### Deployment sequence

1. Confirm the host supports the selected PHP version and extensions.
2. Create the MySQL database and restricted database user.
3. Upload or deploy the release.
4. Install production dependencies.
5. Build frontend assets.
6. Configure `.env`.
7. Generate the application key for the new installation.
8. Point the web document root at Laravel’s `public` directory.
9. Run migrations.
10. Seed only the intended demonstration data.
11. Configure writable Laravel storage directories.
12. Enable HTTPS.
13. Set `APP_DEBUG=false`.
14. Verify login, forms, charts, imports, and reports.
15. Test from another device.

Laravel’s deployment guidance explicitly requires serving the public entry point and disabling debug output in production. [laravel.com](https://laravel.com/framework/docs/deployment?utm_source=chatgpt.com)

Keep MySQL accessible to the application server; it does not need to be publicly exposed.

**Local backup:** Run Laravel and MySQL on a prepared laptop with compiled assets. Test access from another device on a local network. This demonstrates internet-independent operation while the local server remains available.

**Completion condition:** Both the hosted version and local backup complete the presentation journey.

**Depends on:** Phase 20.

---

## Phase 22 — Prepare submission, presentation, and handover

**Purpose:** Ensure the work is understandable, reviewable, and ready on time.

**Create:**

- Project description.
- Repository README.
- Architecture explanation.
- Installation and deployment instructions.
- NASA source list.
- Test summary.
- Known limitations.
- Short demonstration recording.
- Presentation slides.
- Team contribution summary.

### Suggested presentation story

1. **Problem:** An astronaut needs to notice changing indicators during a mission.
2. **Input:** The astronaut completes a daily check-in.
3. **Change:** A simulated trend develops.
4. **Explanation:** OrbitCare shows why a rule triggered.
5. **Action:** A follow-up is recorded.
6. **Resilience:** The local version works without external internet.
7. **Evidence:** Explain NASA sources and prototype limitations.

Verify the current submission format, required links, video limits, and deadlines with the organisers.

Based on your supplied schedule, freeze features well before Sunday’s **13:00 presentations**. Reserve the final hours for testing and rehearsal.

**Completion condition:** Another teammate can present the project, and another developer can install it using the documentation.

---

## Suggested allocation across your six teammates

These are ownership groups, not isolated workstreams. Each owner should have a reviewer.

| Teammate | Primary ownership | Phases |
|---|---|---|
| 1 | Coordination, research, submission | 1, 2, 3, 22 |
| 2 | Design and shared interface | 4, 5, 9 |
| 3 | Foundation, database, access control | 6, 7, 8, 10 |
| 4 | Data collection and imports | 11, 12 |
| 5 | Baselines, rules, alerts, follow-ups | 13, 14, 16 |
| 6 | Dashboard, crew views, reports, scenarios | 15, 17, 18, 19 |
| Whole team | Integration, testing, deployment | 20, 21 |

Teammates 3, 5, and 6 have substantial technical workloads. Once design and research outputs are agreed, teammates 1 and 2 should help with testing, templates, documentation, and demonstration preparation.

## Build in these integration milestones

| Milestone | What must work |
|---|---|
| Foundation | Login, MySQL, roles, shared layout |
| First complete journey | Check-in saved and visible on dashboard |
| Core intelligence | Data evaluated and explained in an alert |
| Complete response | Alert acknowledged and follow-up recorded |
| Presentation release | Seeded scenarios, tests, deployment, documentation |

**The first complete journey is your most important milestone.** Get one astronaut from login to check-in to dashboard early. Then add rules, follow-ups, and supporting screens around that working path.

If time becomes tight, reduce CSV import, administration polish, advanced filters, and scenario playback. Preserve the core: **record indicators, show trends, explain a change, and document follow-up.**