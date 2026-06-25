# CFIP System — Complete Design & Technical Overview
**Purpose of this document:** This is a detailed description of the CFIP System built so far, intended for Claude to use as context when generating new UI/UX design ideas, feature suggestions, and redesign concepts.

---

## 1. What Is This System?

The **CFIP System** (Certified Financial Investigator Programme System) is a **Learning Management Analytics Dashboard** built with Laravel (PHP) and vanilla CSS/JS. It is used by the Malaysian government and associated agencies to manage and monitor participants of the CFIP Foundation Programme — a structured training programme for financial investigators across various government departments and agencies.

The system **does not host course content** — the actual e-learning modules live in a third-party LMS called **iSpring**. This system pulls learner result data from iSpring via synced database tables and presents analytics, progress tracking, and reporting dashboards on top of that data.

---

## 2. Technology Stack

- **Backend:** Laravel 11 (PHP)
- **Frontend:** Vanilla HTML/CSS/JavaScript — no React, Vue, or Tailwind. All styles are hand-written in a custom `dashboard-admin.css`.
- **Font:** DM Sans (Google Fonts) — weights 300, 400, 500, 600, 700
- **Charts:** Chart.js (CDN)
- **PDF Export:** jsPDF (CDN)
- **Database:** MySQL (via Laragon local server)
- **External LMS:** iSpring (course content, enrollment, learner results synced via Artisan commands)
- **Dark Mode:** Supported via CSS class toggle, persisted in localStorage

---

## 3. Colour Palette (Current)

The current design uses a **corporate blue-based palette**:

| Role | Colour | Hex |
|------|--------|-----|
| Primary / Brand | Blue | `#1a4fa8` / `#4f6ef7` |
| Success / Pass | Green / Teal | `#1d9e75` / `#22c7b8` |
| Warning / In Progress | Amber | `#f7b84f` / `#f59e0b` |
| Danger / Failed | Red | `#ff6b6b` / `#e24b4a` |
| At-Risk | Red variant | `#b91c1c` |
| Not Started | Grey | `#d1d5db` / `#9ca3af` |
| Background (light) | Off-white | `#f8fafc` |
| Card background | White | `#ffffff` |
| Border | Light grey | `#e5e7eb` |
| Text primary | Dark navy | `#111827` |
| Text secondary | Medium grey | `#6b7280` |

The login page uses a distinct **sky-blue gradient** (`#e0f0ff → #4a95d9`) as the full-page background.

---

## 4. User Roles

There are **three user roles**, each with a different view scope:

### Role A — Super Admin (Administrator)
- Sees **all data across all agencies and all cohorts**
- Has access to all pages: Dashboard Overview, Domain Analytics, Module Analytics, Student Progress, Report Log, Settings
- Login redirects to the **Level Analytics (Overview) page**
- Can filter by any Agency or Cohort

### Role PC — Program Coordinator
- Sees data **scoped to their own department/agency only**
- Same pages as Admin but without the Agency filter (they already are the agency)
- Login redirects to the **Level Analytics (Overview) page** (PC version)
- Cohort filter is still available within their scope

### Role L — Learner (Student)
- Has a **read-only personal dashboard** showing their own progress
- Simpler sidebar (learner-sidebar)
- Cannot see other learners' data
- Currently shows **static/dummy data** — real learner-specific data connection is not yet built

---

## 5. Navigation / Sidebar

The sidebar is **collapsible** (state persisted in localStorage). It sits on the left side of the screen. When collapsed, only icons are shown (labels hidden).

### Sidebar Sections & Menu Items:

**OVERVIEW**
- Home (house icon) → goes to the Level Overview / Dashboard page (admin.analytics.levels or pc.analytics.levels)

**ANALYTICS** (expandable sub-menu)
- Domain (→ Domain Analytics page)
- Module (→ Module Analytics page)
- Badges & Certificates (disabled — "Coming Soon")

**MANAGEMENT**
- Student Progress (→ learner table with progress bars)

**REPORTS**
- Report Log (→ history of generated reports)

**SYSTEM**
- Settings (→ profile and preferences page)

**Profile strip at bottom of sidebar:**
- Shows user initials (2-letter avatar), full name, role label (Super Admin / Program Coordinator)

**Logout button** at very bottom of sidebar.

---

## 6. Topbar (Header)

Every page has a **topbar** at the top of the main content area. It contains:
- Left: **Page title** (e.g., "Dashboard — Overview", "Foundation", "Student Progress")
- Centre: **Agency and Cohort dropdowns** (filter bar — only on analytics pages)
- Right: 
  - **API status dot** (green = iSpring API reachable, red = unreachable)
  - **User chip** (avatar initial circle + user name)
  - Sometimes: **Refresh button** or **Generate Report button**

---

## 7. Pages — Detailed Breakdown

---

### PAGE 1: Login Page
**URL:** `/` and `/login`
**Accessible to:** Everyone (guest)

#### Design:
- Full-screen page with an animated **sky-blue gradient background** (`#e0f0ff` → `#4a95d9`)
- 10 **floating semi-transparent circular bubbles** animate upward continuously (CSS keyframe `riseUp`)
- The centre shows the **CFIP logo** (PNG image) with a pop-in animation on load
- Below the logo: pulsing text "CLICK ANYWHERE TO SIGN IN" with a downward arrow — both fade in and out
- Clicking anywhere on the page triggers a **modal overlay** (dark backdrop with blur effect — `backdrop-filter: blur(10px)`)
- The **login card** slides up from below with a spring animation
- Card is white, rounded (22px border-radius), with a layered box shadow
- Inside the card: CFIP logo (smaller), "Welcome Back" title, "Sign in to continue" subtitle
- Two fields: **User ID** and **Password**
- **Sign In** button with blue gradient (`#3a85d4` → `#1e5faf`), hover lifts it slightly
- Press Escape or click outside the card to close modal
- If login fails, modal auto-opens and shows an error banner inside the card

#### Logic:
- Login uses **User ID** (not email) as the username field
- Password is bcrypt-hashed and compared with `Hash::check()`
- On success: redirects by role:
  - Admin → `/admin/analytics/levels`
  - PC → `/pc/analytics/levels`
  - Learner → `/learner/dashboard`
- Session-based authentication (Laravel Auth)

#### Data Used:
- `users` table: `user_id`, `password`, `role`, `name`

---

### PAGE 2: Dashboard Overview / Level Analytics (Home Page for Admin & PC)
**URL:** `/admin/analytics/levels` or `/pc/analytics/levels`
**Accessible to:** Admin, PC

This is the **default landing page after login**. It gives a full programme-level summary.

#### Design:
- Left sidebar + topbar layout
- **Welcome Banner** — blue-bordered info strip at top with: "Good [morning/afternoon/evening], [Name]. Here is the CFIP program summary as of [date]."
- **4 KPI cards** in a 4-column horizontal grid
- **Middle row** (2 columns, 60/40 split): Level Progress bars on the left, At-Risk Learners panel on the right
- **Bottom row** (3 equal columns): Domain Completion bars, Weakest Modules list, Recent Activity feed
- **Generate Report** button in topbar (generates a basic PDF with jsPDF)
- **API status dot** in topbar
- Supports dark mode

#### KPI Cards (4 cards):
1. **Total Enrollment** (blue) — count of distinct learners with any activity, across all agencies
2. **Course Completion Rate %** (teal) — percentage of "pass" statuses out of total expected across Entry Level
3. **Active Learners** (amber) — count of learners currently "in progress" in at least one module
4. **At-Risk Learners** (red) — count of learners with <30% average progress AND inactive for 14+ days

Each card has: large number value, a label, an icon, and a sub-label ("Across all agencies", "Currently in a module", etc.)

#### Level Progress Section (left, 60%):
- Lists all programme levels (Entry, Intermediate, etc.) as horizontal rows
- Each row: Level name, a horizontal progress bar (filled % = pass rate for that level), percentage text, and an "Active" or "Not Started" badge (green/grey pill)
- Clicking a level row navigates to the Domain Analytics page filtered to that level

#### At-Risk Learners Section (right, 40%):
- Header shows total at-risk count as a red pill badge
- Lists top at-risk learners with: coloured avatar (initials), name, department, days since last active, and their current progress % in red
- "View all X at-risk learners →" link to Student Progress page

#### Domain Completion Section (bottom-left):
- Shows domains within Entry Level only
- Each domain: label, horizontal bar, percentage
- Only Entry Level is shown here as it's the most active level

#### Weakest Modules Section (bottom-centre):
- Top 3 modules by lowest average quiz/assessment progress (minimum 4 attempts required to qualify)
- Each item: 3-letter initial thumbnail (red background), module name, horizontal mini bar, percentage label in red

#### Recent Activity Feed (bottom-right):
- Live feed of most recent learner activities across the programme
- Each item: coloured dot (green=pass, blue=in-progress, red=failed), learner name in bold, action description (e.g. "completed Financial Crime Typologies — 88%" or "is working on AML Frameworks"), time ago

#### Data Used:
- `learner_module_results`: `user_id`, `course_id`, `module_title`, `completion_status`, `progress`, `access_date`, `completion_date`
- `levels`: `id`, `name`, `order`
- `domains`: `id`, `name`, `level_id`
- `domain_courses`: `domain_id`, `course_id`
- `departments` (for agency name lookup)
- `users_ispring` (for learner name resolution from iSpring user IDs)

---

### PAGE 3: Foundation Dashboard (Admin's Secondary Dashboard)
**URL:** `/admin/dashboard`
**Accessible to:** Admin only

This is a **course-level dashboard** focused on the 3 Foundation Modules (FD01, FD02, FD03).

#### Design:
- Same sidebar + topbar layout
- Page title: "FOUNDATION" with a dropdown arrow
- Has a **loading overlay** (spinner + "Refreshing data…" text) that appears during AJAX filter updates
- **4 KPI cards** (Total Enrollment, Completion Rate %, In Progress count, Not Started count)
- **2 chart cards** side by side:
  - Left (larger): Bar chart — Foundation Module Progress
  - Right (smaller): Doughnut chart — Foundation Module Enrollment
- **2 topic cards** side by side: Weakest Topics and Strongest Topics

#### Bar Chart (Foundation Module Progress):
- **Grouped bar chart** using Chart.js
- X-axis: 3 labels — FD01, FD02, FD03 (the three Foundation modules)
- Y-axis: learner count
- 3 datasets (bars) per group:
  - Blue bars = Pass count
  - Amber bars = In Progress count
  - Red bars = Failed count
- Custom legend at bottom of chart card (dots + labels)
- Dark tooltip background (#1a1f36)
- **Cohort dropdown** and **Agency dropdown** sit inside the chart card header — both trigger AJAX filter

#### Doughnut Chart (Foundation Module Enrollment):
- 60% cutout doughnut
- 3 segments: FD01 (blue #4f6ef7), FD02 (pink #ff9eb5), FD03 (teal #22c7b8)
- No built-in legend — custom HTML list below chart showing: coloured dot, FD code, count · percentage
- Hover tooltip shows label, count, and percentage

#### Weakest/Strongest Topics Cards:
- Each card lists up to 3 topics
- Each topic: 3-letter initial avatar (red for weak, blue for strong), topic name, horizontal mini progress bar, "X% Correct" label
- Weak bar is red (`#e24b4a` fill), strong bar is green

#### AJAX Filter Logic:
- When Agency or Cohort dropdown changes, calls `GET /api/filter-bar-chart?agency=X&cohort=Y`
- Returns `{success: true, barChart: {FD01: {pass, progress, failed}, FD02: ..., FD03: ...}}`
- Chart is updated in-place via `barChart.data.datasets[x].data = [...]` + `barChart.update()`
- Loading overlay shown during fetch

#### Data Used:
- Same as Level Analytics page but scoped to FD courses only
- FD courses mapped via `FD_COURSES` constant in DashboardController

---

### PAGE 4: Domain Analytics
**URL:** `/admin/analytics/domains?level_id=X` or `/pc/analytics/domains?level_id=X`
**Accessible to:** Admin, PC

Shows analytics for all **learning domains within a selected programme level**.

#### Design:
- Topbar has a **Level dropdown** (styled as a pill button, actual select hidden behind it) — changing it reloads the page with the new level_id
- **Generate Report** button (PDF)
- 4 KPI cards (same metrics as other pages)
- **Domain Cards Grid** — `auto-fill minmax(260px, 1fr)` grid
- **Topics Row** at bottom — Weakest Modules and Strongest Modules

#### Domain Cards:
Each domain gets its own card with:
- Domain name (bold)
- Large pass rate percentage (in blue, 22px font)
- Thin horizontal progress bar (filled = pass rate)
- Status pills row: green pill "✓ X passed", amber pill "⟳ X in progress", red pill "✕ X failed", grey pill "○ X not started" (only shows pills with >0 count)
- "View modules →" link at bottom leading to Module Analytics filtered to that domain

#### Filters:
- Agency and Cohort dropdowns in topbar (standard topbar-filters partial)
- Level dropdown in the page title area

#### Data Used:
- `domain_courses`: maps domains to iSpring course IDs
- `learner_module_results`: for pass/progress/failed/not_started counts per domain
- `domains` and `levels` tables

---

### PAGE 5: Module Analytics
**URL:** `/admin/analytics/modules?domain_id=X` or `/pc/analytics/modules?domain_id=X`
**Accessible to:** Admin, PC

Shows detailed analytics for every **course module within a selected domain**.

#### Design:
- Topbar has a **Domain dropdown** (grouped by level in `<optgroup>` tags)
- **Generate Report** button (PDF — exports the visible table rows as a formatted table)
- If the domain has no courses linked: shows a "Coming Soon" empty state (icon + title + subtitle + grey pill)
- Otherwise shows: 4 KPI cards, Module Table, Topics Row

#### Module Table:
- Search box (filters rows client-side by module code)
- Columns: **Module** (course code in blue bold), **Distribution** (stacked mini bar), **Passed** (green), **In Progress** (amber), **Failed** (red), **Not Started** (grey), **Total**, **Pass Rate** (coloured badge)
- Distribution bar: stacked segments — pass (dark green), progress (amber), failed (red), not_started (light grey) — proportional widths
- Pass Rate badge colours:
  - ≥70% = green (high)
  - 40–69% = amber (mid)
  - 1–39% = red (low)
  - 0% = grey (zero)
- Sortable columns (Pass count, In Progress, Total, Pass Rate) — click header to sort, arrow indicator
- Default sort: Pass Rate descending
- PDF export includes full table data with alternating row shading

#### Data Used:
- `domain_courses` → course_ids for the domain
- `learner_module_results` grouped by `module_title` to get per-module stats

---

### PAGE 6: Student Progress
**URL:** `/admin/students` or `/pc/students`
**Accessible to:** Admin, PC

A **searchable, filterable table** of all learners with their progress status.

#### Design:
- **Toolbar** above the table: search box, Status dropdown, Cohort dropdown (server-side), Agency dropdown (admin only, server-side), Export CSV button
- Row count chip ("X learners shown")
- Sticky table header
- Each row: clickable (opens detail drawer)

#### Table Columns:
1. **Learner** — avatar circle (coloured by status) with 2-letter initials, full name below, department name in small grey text
2. **Progress** — percentage text + thin horizontal progress bar in status colour
3. **Status** — coloured badge pill with dot indicator:
   - Green = Completed (≥80%)
   - Blue = On Track (30–79%)
   - Amber = At Risk (<30% with activity)
   - Grey = Not Started (no results at all)
4. **Last Active** — relative time (e.g. "3 days ago")
5. **Action** — eye icon button to open detail drawer

#### Status Classification Logic:
- `not_started`: no results at all (has_results = false) OR avg_progress = 0
- `completed`: avg_progress ≥ 80
- `on-track`: avg_progress ≥ 30
- `at-risk`: avg_progress < 30 (but has activity)

#### Client-side Filters (instant):
- Search by name (input filter on `data-name` attribute)
- Filter by status badge class

#### Server-side Filters (page reload):
- Cohort (`?cohort=group_id`)
- Agency (`?agency=department_id`) — Admin only

#### Detail Drawer (slide-in panel):
- Slides in from the right with a smooth animation (right: -420px → right: 0)
- Dark overlay behind it (click to close, Escape to close)
- Content:
  - Large coloured avatar circle (60px) with initials
  - Full name (17px bold)
  - Department name below
  - Divider
  - "Overall Progress" label
  - Large percentage number + status badge side by side
  - 10px high progress bar (coloured by status)
  - Divider
  - 2-column meta grid: Department, Last Active (full date), Status (text)

#### CSV Export:
- Client-side CSV generation from visible (non-filtered-out) rows
- Columns: Learner Name, Department, Progress %, Status, Last Active
- Downloads as `student_progress.csv`
- Also logs the export to `report_logs` via POST API

#### Data Used:
- `users_ispring`: `user_id`, `department_id`, `fields` (JSON — contains first_name, last_name)
- `learner_module_results`: aggregated per user — avg progress, last active date, has_results flag
- `departments`: for agency name lookup

---

### PAGE 7: Learner Dashboard (Student View)
**URL:** `/learner/dashboard`
**Accessible to:** Learner role only

**NOTE: This page currently shows static/dummy data. Real per-learner data is not yet wired up.**

#### Design:
- Uses a simpler **learner-sidebar** (same structure but narrower menu)
- **Welcome Strip** at top — dark navy gradient banner (`#1e2a4a → #2d3f6e`) with:
  - Left: "Welcome back," + learner's full name + "CFIP Foundation Programme · Learner"
  - Right: a small badge showing "Current Programme: Foundation (CI 01)"
- **4 KPI cards** in a grid:
  1. Current Level (blue) — shows "CI 01" / Foundation Level
  2. Domains Completed (teal) — shows "3/5" as a fraction with remaining count
  3. Badges Earned (purple) — shows "—" with a "Coming Soon" pill (feature not built yet)
  4. Overall Status (green) — shows "In Progress" with an "Active Learner" badge
- **2 chart cards** (same layout as Admin Foundation Dashboard):
  - Bar chart: Foundation Module Progress (FD01/FD02/FD03 with pass/progress/failed/not_started)
  - Doughnut chart: Foundation Module Enrollment
- **2 topic cards**: Weakest Topics and Strongest Topics (all dummy data currently)

#### Dummy Data Shown:
- Weak topics: Financial Crime Typologies (38%), AML Frameworks (45%), KYC & CDD (52%)
- Strong topics: Introduction to Financial Investigation (92%), Ethics (87%), Reporting Obligations (81%)

---

### PAGE 8: Settings
**URL:** `/settings`
**Accessible to:** All authenticated users (Admin, PC, Learner)

#### Design:
- Two **accordion sections** — each has a header that expands/collapses with a chevron rotation animation
- Opening a section adds a left blue border and "is-open" state

#### Section 1 — Profile Settings (open by default):
- Left column: Large avatar circle (96px) with initials, gradient background (blue to green), edit pencil icon overlay (non-functional — "photo upload coming soon"), role label below (Administrator / Program Coordinator / Student)
- Right column: Profile form with:
  - **Full Name** (editable after clicking "Edit Profile")
  - **Username** (read-only, disabled — "Username cannot be changed")
  - **Email** (editable after clicking "Edit Profile")
  - In view mode: "Edit Profile" and "Change Password" buttons
  - In edit mode: "Save Changes" and "Cancel" buttons
- **Change Password sub-section** (hidden, slides open when "Change Password" clicked):
  - Current Password field
  - New Password field (min 8 chars)
  - Confirm New Password field
  - "Update Password" and "Cancel" buttons
- Flash messages shown at page top: green success alerts and red error alerts

#### Section 2 — System Preferences:
- **Data Refresh Interval** dropdown (15 min / 30 min / 1 hour / Never) — persisted in localStorage (no server implementation yet)
- **Dark Mode toggle** — iOS-style toggle switch, persists in localStorage and immediately applies to all pages via `html.dark-mode` class

#### Data Used:
- `users` table: `name`, `email`, `user_id`, `role`, `password`
- Change Password validates current password via `Hash::check()`, updates with `Hash::make()`

---

### PAGE 9: Report Log
**URL:** `/admin/reports` or `/pc/reports`
**Accessible to:** Admin, PC

A **history log of all report downloads** generated by the user.

#### Design:
- **Summary strip** at top: blue "X total" pill, red "X PDF" pill, green "X Excel / CSV" pill
- **Search box** (filters table client-side by report title)
- Row count chip
- Clean table in a card

#### Table Columns:
1. **Report Title** — e.g. "Entry Level — Domain Analytics", "Student Progress Report"
2. **Format** — coloured badge: red "PDF" badge or green "Excel" / "CSV" badge
3. **Date & Time** — sortable column (toggle ascending/descending by clicking header, arrow indicator)
4. **Status** — always "Completed" in green (no async generation — all reports are synchronous client-side)

#### Data Used:
- `report_logs` table: `title`, `format`, `status`, `created_at`, `user_id`
- Filtered to the current logged-in user's reports

---

## 8. Data Flow (How Analytics Are Built)

### Source of Truth
All learner progress comes from iSpring LMS, synced via Artisan console commands that hit the iSpring API and write to local MySQL tables:

| Command | What It Syncs |
|---------|---------------|
| `SyncUsers` | iSpring users → `users_ispring` |
| `SyncDepartments` | Agencies → `departments` |
| `SyncGroups` | Cohorts → `groups` |
| `SyncEnrollments` | Course enrollments → `enrollments` |
| `SyncModules` | Course modules → `modules` |
| `SyncContent` | Content items → `contents` |
| `SyncCourseModules` | Course module mappings → `course_modules` |
| `SyncLearnerResults` | Learner progress → `learner_module_results` |
| `MapCourseModules` | Post-sync mapping → `course_module_map` |

### Status Resolution Rules
Raw `completion_status` values from iSpring are resolved to 4 standard statuses:

**For Lessons:**
- `complete` or `completed` → **pass**
- `in_progress` → **progress**
- anything else → **not_started**

**For Quiz Lessons and Module Assessments:**
- `passed` → **pass**
- `in_progress` → **progress**
- `failed` → **failed**
- anything else → **not_started**

**The system only counts modules that contain the words:** "Lesson", "Quiz", "Assessment" in their title. Pure video modules and intro pages are excluded.

### Key Analytics Fields (learner_module_results)
- `user_id` — iSpring user ID
- `course_id` — iSpring course ID
- `module_title` — e.g. "Financial Crime Typologies — Quiz Lesson"
- `completion_status` — raw string from iSpring
- `progress` — integer 0–100
- `access_date` — when the learner started
- `completion_date` — when completed (nullable)
- `time_spent` — integer seconds
- `views_count` — how many times viewed
- `is_overdue` — boolean flag

### At-Risk Calculation
A learner is "at risk" when both conditions are met:
- Average progress across all their modules < 30%
- Last activity date was more than 14 days ago

### Topic Strength Calculation (Weak/Strong Topics)
- Only "Quiz Lesson" and "Module Assessment" modules are considered
- Grouped by `module_title`, average `progress` calculated
- Minimum 4 learner attempts needed to qualify
- Bottom 3 = Weakest Topics
- Top 3 = Strongest Topics

---

## 9. Programme Structure

The CFIP Foundation Programme is structured as:

```
Levels (e.g. Entry, Intermediate, Advanced, Professional)
  └── Domains (learning areas within each level, e.g. "Financial Crime", "AML")
        └── Domain Courses (mapped iSpring course IDs)
              └── Modules (individual lessons, quizzes, assessments)
```

The Foundation modules (FD01, FD02, FD03) are hardcoded as special course codes tracked at the top-level Foundation dashboard.

---

## 10. Report Generation

Reports are generated **client-side in the browser** using jsPDF. There is no server-side PDF generation. Each analytics page has a "Generate Report" button that:
1. Creates a new A4 PDF document
2. Adds a dark navy header banner with "CERTIFIED FINANCIAL INVESTIGATOR PROGRAM" in white
3. Adds the report type and date on the right
4. Adds page content (summary stats, table data from the DOM)
5. Adds a footer on every page: "Generated by: [name] | Date: [date]" and "CFIP — CONFIDENTIAL"
6. Saves as a local download (e.g. `CFIP_Domain_Analytics_2026-06-18.pdf`)
7. Logs the report to the database via POST API

Reports currently do NOT include charts — just text and tables.

---

## 11. What Is Currently Missing / Placeholder

These features are marked as "Coming Soon" or not yet implemented:
- **Badges & Certificates** (sidebar menu item is disabled)
- **Learner Dashboard real data** (currently all static dummy values)
- **Photo upload for profile** (pencil icon exists but does nothing)
- **Data refresh interval** (preference saved but no auto-refresh logic)
- **Push notifications or alerts for at-risk learners**
- **Charts in PDF reports** (only text/table data currently)
- **Learner-level module drilldown** (individual learner's module-by-module view not built)
- **Historical trend data** (all charts show current snapshot, no time-series)
- **PC Dashboard** (`/pc/dashboard`) exists but is separate from the Level Analytics flow
- **API refresh** endpoint is a stub (returns `{success: true}` and triggers full page reload)

---

## 12. Current Design Weaknesses (For Claude to Address)

Based on the current implementation, here are the areas where the design could be significantly improved:

1. **Learner Dashboard is static** — shows hardcoded dummy data with no real connection to the learner's own iSpring results
2. **No time-series / trend charts** — all analytics are point-in-time snapshots; there is no way to see improvement over weeks/months
3. **No notification system** — at-risk learners are shown in a panel but there's no alert/email system
4. **Reports are basic PDF** — just text with header/footer, no charts or graphs embedded
5. **No individual learner profile page** — clicking a learner in Student Progress opens a side drawer with basic info but no module-level drilldown
6. **Mobile/responsive design not addressed** — the layout uses fixed sidebar widths and is not mobile-optimised
7. **PC Dashboard page exists but is redundant** — Admin and PC currently share the same Level Analytics page as home, but there's a separate `/pc/dashboard` and `/admin/dashboard` (Foundation view) that is accessed separately
8. **Dark mode is partially implemented** — toggled via localStorage and CSS class but some UI elements may not fully support it
9. **No search on analytics pages** — only the Module page and Student Progress have search/filter; Level and Domain pages do not
10. **Badges feature is blocked** — a full gamification system with badges and certificates is planned but not started

---

## 13. Summary Table of All Pages

| Page | URL | Roles | Key Data Shown | Charts |
|------|-----|-------|----------------|--------|
| Login | `/` | Guest | — | None |
| Level Overview (Home) | `/admin/analytics/levels` | Admin, PC | 4 KPIs, level progress bars, at-risk panel, domain bars, weak modules, recent activity | None |
| Foundation Dashboard | `/admin/dashboard` | Admin | 4 KPIs, FD01/02/03 bar chart, enrollment doughnut, weak/strong topics | Bar + Doughnut |
| Domain Analytics | `/admin/analytics/domains` | Admin, PC | 4 KPIs, domain cards with pass rates, weak/strong topics | None (cards only) |
| Module Analytics | `/admin/analytics/modules` | Admin, PC | 4 KPIs, sortable module table with stacked bars, weak/strong topics | Mini stacked bars |
| Student Progress | `/admin/students` | Admin, PC | Learner table with progress, status badges, detail drawer | Mini progress bars |
| Learner Dashboard | `/learner/dashboard` | Learner | 4 personal KPIs, FD bar chart, doughnut, weak/strong topics | Bar + Doughnut |
| Settings | `/settings` | All | Profile form, password change, dark mode toggle, refresh interval | None |
| Report Log | `/admin/reports` | Admin, PC | Report download history table | None |

---

## 14. Design Language Summary

The current design is a **clean, corporate SaaS-style dashboard** with:
- White cards with subtle shadows and light grey borders
- Rounded corners (8–14px)
- Icon-based SVG icons throughout (no icon font library — all inline SVG)
- Coloured KPI cards with a left-side colour accent bar (via `::before` pseudo-element)
- Small coloured icon boxes inside KPI cards (e.g. blue background for the enrollment icon)
- Consistent typography: DM Sans, fairly small (11–14px for most UI, 28–34px for KPI numbers)
- Minimal animation: hover states, progress bar fills, sidebar collapse
- No gradients in the main dashboard (only the login page uses gradients)
- No images in the dashboard (only SVG icons and the sidebar logo)
- Status is consistently communicated through colour: blue=info, green=pass/success, amber=warning/in-progress, red=failed/danger
