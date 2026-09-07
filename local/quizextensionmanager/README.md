# local_quizextensionmanager

Lets students request a time extension (new close date, time limit and/or
number of attempts) on a `mod_quiz` activity, and lets teachers review,
approve or deny those requests. On approval, the plugin creates/updates a
`mod_quiz` user override for the student. See `PLUGIN_SPEC.md` at the
repository root for the full functional spec.

This plugin owns all data and logic. The companion subplugin
[`quizaccess_quizextensionmanager`](../../mod/quiz/accessrule/quizextensionmanager)
places the "Request extension" entry point on the quiz view page via the
quiz access rule `description()` hook, and delegates everything else back
to this plugin.

**Status:** the request/approval workflow is implemented: eligibility
checks, request CRUD, the student request form and "my requests" page, the
teacher approve/deny dashboard and course-wide report, per-quiz/per-course
settings pages, `quiz_overrides` integration on approval, and
approved/denied notifications. See `PLUGIN_SPEC.md` at the repository root
for the full functional spec this implementation follows.

## `quiz_overrides` integration

`classes/override_manager.php` was verified against the real Moodle 5.2.2
core source (`mod/quiz/classes/local/override_manager.php` and
`mod/quiz/classes/quiz_settings.php`). It delegates to mod_quiz's own
`\mod_quiz\local\override_manager::save_override()` (obtained via
`\mod_quiz\quiz_settings::create($quizid)->get_override_manager()`) rather
than writing to `{quiz_overrides}` directly, so cache purging
(`quiz_overrides_cache_manager`), `user_override_created`/`_updated` event
triggering, calendar event refresh, and in-progress-attempt recalculation
(`quiz_update_open_attempts()`, `quiz_update_events()`) all happen exactly
as they would from the native "Add user override" screen.

Two deliberate points worth knowing:

- `save_override()` does not check capabilities itself
  (`require_manage_capability()` is a separate opt-in call that checks
  `mod/quiz:manageoverrides`). This plugin does not call it, since approval
  is already gated on `local/quizextensionmanager:manage` at the page
  level -- per the spec, either capability is an acceptable gate.
- `save_override()` resets every override-able quiz field (`timeopen`,
  `timeclose`, `timelimit`, `attempts`, `password`) that isn't included in
  the data you pass it back to "use the quiz default" (null) on an update.
  `override_manager::apply_override()` explicitly carries forward an
  existing override's `timeopen`/`password` before calling it, so approving
  a request never silently clobbers a `timeopen` or `password` a teacher
  set through Moodle's own override screen, independent of this plugin.

## Installation

1. Copy (or symlink) this directory to `local/quizextensionmanager` in your
   Moodle codebase.
2. Copy (or symlink) the companion subplugin to
   `mod/quiz/accessrule/quizextensionmanager`.
3. Visit **Site administration > Notifications** to run the installer, or
   run:
   ```bash
   php admin/cli/upgrade.php --non-interactive
   ```
4. Configure site-wide defaults at **Site administration > Plugins >
   Local plugins > Quiz extension manager**.
5. Assign the `local/quizextensionmanager:request` and
   `local/quizextensionmanager:manage` capabilities as needed (they default
   to the Student and Teacher/Editing teacher/Manager archetypes
   respectively).

## Database tables

- `quizextensionmanager_request` — one row per student extension request.
- `quizextensionmanager_quizset` — per-quiz configuration (spec:
  "quiz_settings"; abbreviated to fit Moodle's 28-character table name
  limit).
- `quizextensionmanager_crsset` — per-course configuration (spec:
  "course_settings"; abbreviated for the same reason).

## Privacy

This plugin stores personal data (requests, reasons, reviewer comments,
and uploaded documentation files) and implements a full
`\core_privacy\local\request\plugin\provider`. See
`classes/privacy/provider.php`.

## Testing

- `tests/*.php` — PHPUnit coverage for eligibility rules, request state
  transitions, and override creation/update on approval.
- `tests/behat/*.feature` — Behat coverage for the main UI flows: a student
  submitting/editing/cancelling a request and being blocked from a
  duplicate pending one (`request_workflow.feature`), a teacher approving
  (verified via Moodle's own "User overrides" page) or denying a request
  (`approval_workflow.feature`), and a teacher enabling/disabling extension
  requests for a quiz (`settings.feature`). Written against, and verified
  step-by-step against, the real Moodle 5.2.2 core source and its own
  `mod_quiz` Behat suite, but not executed end-to-end in this environment
  (no PHP/Selenium available) -- run
  `php admin/tool/behat/cli/init.php` then
  `vendor/bin/behat --tags=local_quizextensionmanager` once in a real dev
  environment.
