# Moodle Plugin: Quiz Time Extension Requests

## Overview

A Moodle plugin that lets students request a time extension on a quiz
attempt, and lets teachers/instructors review, approve, deny, and manage
those requests. The goal is to replace ad-hoc email/message requests for
extra time with a tracked, in-Moodle workflow.

## Plugin Type

Two components, working together:

- **`local_quizextensionmanager`** (main plugin) — owns all data (request
  table, per-quiz/per-course settings), the student request/review forms,
  teacher approval dashboard, notifications, and the logic that creates/
  updates `mod_quiz` user overrides on approval.
- **`quizaccess_quizextensionmanager`** (thin subplugin) — exists solely to
  place the "Request extension" entry point on `mod/quiz/view.php` via the
  quiz access rule `description()` hook, which is Moodle's own sanctioned
  mechanism for adding server-rendered content to the quiz view page before
  an attempt starts (the same mechanism used for password/access-rule
  messages there). It does not gate or restrict attempts — it delegates to
  `local_quizextensionmanager` for all actual logic; it just owns UI
  placement so the button is native, server-rendered, and not dependent on
  client-side DOM injection.

This was chosen over a pure `local_` + JavaScript-DOM-injection approach
(simpler packaging, one component, but fragile against future core/theme
markup changes and needs a no-JS fallback) and over a theme/renderer
override (only takes effect via a custom theme, so it isn't portable to a
site running a different theme).

## Plugin Name
the plugin should be named quizextensionmanager

## Toolchain / Skills
This project will use skills from
[SaadRahman01/claude-moodle-dev](https://github.com/SaadRahman01/claude-moodle-dev)
for Moodle plugin scaffolding, coding standards, and testing conventions.
Use those skills for boilerplate (`version.php`, `lang` files, DB install
XML, capabilities, upgrade steps, behat/PHPUnit scaffolding) rather than
hand-rolling them.

## Core Features

### Student-facing
- Submit a time extension request for a specific quiz,
      including a reason (free text) and requested new date and timelimit and attempts for completion. Request form should show current date / timelimit / attempts.
- Do not allow requests before the quiz is open.
- Only allow requests before the teacher-specified request window for that quiz has closed.
- View status of their own requests (pending / approved / denied) and the total number of extension requests they have used out of total available.
- Receive a notification (Moodle message/email) when a request is
      approved or denied.
- Prevent duplicate/overlapping requests for the same quiz. 
- Allow students to edit or delete their request prior to approval.


### Teacher/instructor-facing
- Provide options per quiz 1) option to allow/disallow extension requests for a particular quiz 2) Option to require a reason 3) options to disallow/permit/require documentation and restrict file types of documentation 4) Option to specify a request window, i.e., Number of days after quiz availability ends where requests are still allowed (or specify a fixed date), with option to allows requests until the end of the course.
- Per course, the teacher/instructor can specify an option to limit the total number of requests students are allowed.
- Per course, the teacher/instructor can specify the default "quiz window" to be number of days past the quiz close date.
- View a list of pending extension requests for quizzes in their course.
- Approve or deny a request from the list, optionally editing the granted extra time and optionally opening the request to see more details.
- Approving a request automatically creates/updates a **user override**
      on the quiz (extending `timeclose` and/or `timelimit` and/or `attempts` for that user but only if those items deviate from the original).
- Leave a comment/reason when approving or denying a request.
- Bulk view of all requests across a course (report/table page).
- Capability-gated: only users with the appropriate capability
      (e.g. `mod/quiz:manageoverrides` or a new custom capability) can
      approve/deny.

### Admin-facing
- Site-wide settings page (e.g. max extension allowed, whether reason
      is required, whether documentation is notused / permitted / required, default documentation filetypes allowed, notification templates).
- Capability definitions for who can request vs. approve.

## Data Model (draft — verified against Moodle schema)

### `mdl_quizextensionmanager_request` (one row per student request)
- `id`
- `quizid`
- `courseid` (denormalized from `{quiz}.course` for cheaper course-level report queries)
- `userid` (student)
- `requestedtimeclose` (nullable — new close date/time being requested)
- `requestedtimelimit` (nullable — new time limit in seconds, matching `{quiz}.timelimit` units)
- `requestedattempts` (nullable — new max attempts)
- `currenttimeclose`, `currenttimelimit`, `currentattempts` — snapshot of the quiz's values *at the time of the request*, so the request/review UI and any later audit still shows what the student was comparing against even if the quiz's own settings change afterward.
- `reason` (text)
- `status` (pending / approved / denied / cancelled — added `cancelled` since students can delete their own request)
- `grantedtimeclose`, `grantedtimelimit`, `grantedattempts` — **separate from the requested values**, since a teacher can approve with different numbers than what was asked for. Without these, you lose the distinction between "what the student asked for" and "what they actually got."
- `quizoverrideid` — FK to `mdl_quiz_overrides.id`. Moodle only allows **one override row per user per quiz**, so if a student is granted a second extension later (or edits are made), the plugin must update this existing override rather than insert a new one. This column is how the plugin finds it.
- `reviewerid` (teacher who actioned it, nullable while pending)
- `reviewreason` (text, optional)
- `timecreated`
- `timereviewed` (nullable — when approved/denied, distinct from `timemodified` which also changes on student edits)
- `timemodified`

Note on documentation uploads: Moodle doesn't store files as a link/column — it uses the **File API** (`mdl_files` table, keyed by `component` + `filearea` + `itemid` + `contextid`). This plugin should register a file area (e.g. `local_quizextensionmanager`, filearea `documentation`, `itemid` = the request's `id`) and implement `local_quizextensionmanager_pluginfile()` in `lib.php` for download access, plus a privacy provider export for those files. No `documentation` column is needed on the request table itself.

### `mdl_quizextensionmanager_quiz_settings` (per-quiz configuration)
- `id`
- `quizid`
- `enabled` (allow/disallow requests for this quiz)
- `requirereason` (bool)
- `documentationmode` (none / optional / required)
- `allowedfiletypes` (text — reuse Moodle's filetypes util for the picker)
- `requestwindowtype` (days-after-close / fixed-date / until-course-end)
- `requestwindowdays` (nullable, used when type = days-after-close)
- `requestwindowdate` (nullable timestamp, used when type = fixed-date)
- `timecreated`, `timemodified`

### `mdl_quizextensionmanager_course_settings` (per-course configuration)
- `id`
- `courseid`
- `maxrequestsperstudent` (nullable = unlimited)
- `defaultrequestwindowdays` (default used when a quiz doesn't override it)
- `timecreated`, `timemodified`

### Site-wide settings
No custom table needed — use Moodle's standard plugin config (`get_config('local_quizextensionmanager', ...)` / admin `settings.php`), which already persists to `mdl_config_plugins`.

Quota display ("requests used out of total available") is computed as
`COUNT(status='approved')` against `maxrequestsperstudent` — see Resolved
Decisions below.

## Workflow

1. Student clicks on the quiz and sees option for "Request extension" next to the "Attempt quiz" button and submits a request. The entry point is on the quiz page before starting the attempt (aka /mod/quiz/view.php page and NOT on attempt.php), rendered by the `quizaccess_quizextensionmanager` subplugin via the access rule `description()` hook, which calls into `local_quizextensionmanager` for the actual form/logic.
2. Teacher sees pending requests (dashboard block, report page, or
   notification).
3. Teacher approves (sets minutes/new close time/number of attempts) or denies (with reason).
4. On approval, plugin writes/updates a `quiz_overrides` row for that
   student for that quiz.
5. Student is notified of the outcome.

## Technical Requirements

- Target Moodle version: _5.2_.
- Follow Moodle coding style (PSR-12-ish + Moodle-specific rules).
- `local/quizextensionmanager` directory conventions:
  `version.php`, `db/install.xml`, `db/access.php`, `db/upgrade.php`,
  `lang/en/local_quizextensionmanager.php`, `classes/`, `templates/` (Mustache),
  `amd/src/` (JS modules), `externallib.php` or `classes/external/` for AJAX.
- `mod/quiz/accessrule/quizextensionmanager` directory conventions for the
  thin subplugin: `rule.php` (extends `quiz_access_rule_base`, implements
  `description()` to render the button/link and delegates everything else
  to `local_quizextensionmanager`), `version.php`,
  `lang/en/quizaccess_quizextensionmanager.php`. It should NOT implement
  `prevent_access()` or similar gating methods — it must not actually
  restrict quiz attempts, only provide the UI entry point.
- Use Moodle's Forms API (`moodleform`) for the request/approval forms.
- Use Moodle's `\core\notification` / messaging API for status updates.
- Add capabilities in `db/access.php`:
  - `local/quizextensionmanager:request` (student)
  - `local/quizextensionmanager:manage` (teacher)
- Include PHPUnit tests for core logic (request creation, override
  creation on approval) and Behat tests for the main UI flows.
- Respect Moodle privacy API (GDPR) — implement `\local_quizextensionmanager\privacy\provider`
  since the plugin stores personal data (requests, reasons, uploaded documentation).

## Out of Scope (for now)

- Extensions for assignments or other activity types (quiz-only for v1).
- Automated approval rules (all approvals are manual in v1).

## Resolved Decisions

- **View page entry point**: implemented as a thin `quizaccess_quizextensionmanager`
  subplugin using the access rule `description()` hook to render the
  "Request extension" button/link on `mod/quiz/view.php`, delegating all
  actual logic to `local_quizextensionmanager`. See Plugin Type above.
- **Quota counting**: "requests used out of total available" counts
  **approved requests only** — denied and cancelled requests do not count
  against a student's quota.