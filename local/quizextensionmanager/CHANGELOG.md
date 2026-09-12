# Changelog

All notable changes to `local_quizextensionmanager` are documented here.

Starting at 0.8, this release number is kept in lockstep with
`PLUGIN_SPEC.md`'s version (previously they'd drifted to 0.7 vs. 0.3.3).

## [0.17] - 2026-09-12

### Added

- `classes/table/requests_table.php`: added a "Reviewer comment" column,
  the same gap `myrequests.php` had before v0.14 fixed it there. The
  course-wide teacher report showed who reviewed a request but never the
  actual comment/reason text left.

## [0.16] - 2026-09-12

### Fixed (test suite only -- no app-code change)

- `tests/quizextensionmanager_test.php` was renamed to `tests/smoke_test.php`
  to match the class name inside it (`smoke_test`, not
  `quizextensionmanager_test`). PHPUnit's filename-based discovery was
  silently skipping the whole file because of the mismatch -- it never
  ran, passed, or failed, just vanished from the test count. The identical
  bug existed in the `quizaccess_quizextensionmanager` subplugin too,
  where it was worse: that smoke test was the subplugin's *only* test
  file, so its entire testsuite was running zero tests ("No tests
  executed!") while still exiting non-failing. Fixed there the same way.
  Confirmed after the fix: `local_quizextensionmanager_testsuite` 21/21,
  `quizaccess_quizextensionmanager_testsuite` 1/1.

## [0.15] - 2026-09-12

### Fixed (test suite only -- no app-code change)

- `tests/behat/settings.feature`: tagged the two scenarios that toggle
  the "Allow extension requests for this quiz" checkbox `@javascript`.
  That field is an `advcheckbox` (a real checkbox plus a same-named
  hidden `"0"` fallback), and a debug dump confirmed `$_POST['enabled']`
  was entirely absent when Behat unchecked it under the non-JS
  BrowserKit driver -- a known BrowserKit/Goutte limitation with
  same-named checkbox+hidden pairs, not a bug in `quizsettings.php`,
  `quiz_settings_form.php`, `eligibility.php`, or `extension_link.php`
  (all confirmed correct). Resolves the "known issue" noted in 0.14.

## [0.14] - 2026-09-12

Two bugs found by actually running the Behat suite for the first time.

### Fixed

- `request.php`: the documentation filemanager's `accepted_types` was
  built by splitting `allowedfiletypes` on spaces, but the `filetypes`
  picker widget introduced in v0.11 stores its value comma-separated
  (`MoodleQuickForm_filetypes::exportValue()` does `implode(',', ...)`).
  This silently broke every documentation upload (Moodle received one
  unsplit `"document,image"`-style token instead of two real types).
  Also updated the site-wide default in `settings.php` to already be in
  the correct comma-separated format.

### Added

- `myrequests.php`: added Reason and Reviewer comment columns. Both were
  captured from the very start but never actually displayed anywhere on
  the student's own status page -- a longstanding gap, not a regression.

### Known issue

- A disabled quiz still shows "Request extension" to students. Confirmed
  as a save-path bug in `quizsettings.php`/`quiz_settings_form.php` (the
  saved settings row is stuck at all schema defaults regardless of what
  the form submitted) -- root cause not yet found.

## [0.13] - 2026-09-07

### Added

- `tests/behat/documentation.feature` (new): a teacher configures a quiz's
  documentation mode, a student uploads a file with their request (via
  the real `I upload "..." file to "..." filemanager` Behat step, tagged
  `@_file_upload`), and the teacher sees it in the approve modal. Fixture
  at `tests/fixtures/test-documentation.pdf`.
- `tests/notification_manager_test.php` (new): PHPUnit coverage for
  `notification_manager::send_new_request()` -- the teacher gets notified,
  the student doesn't, and approving a request doesn't re-send it.
- `tests/behat/request_workflow.feature`: scenario for requesting an
  additional attempt via the checkbox (added in 0.6, previously untested).
- `tests/behat/settings.feature`: scenario confirming a teacher doesn't
  see the student-facing "Request extension" link (added in 0.7,
  previously untested).

No runtime behavior changed in this release -- test coverage only.

## [0.12] - 2026-09-07

### Changed

- `form:grantedtimeclose`/`form:grantedtimelimit`/`form:grantedattempts`
  (the approve modal's editable field labels) renamed from "Granted..."
  to "Requested..." -- flagged from testing: those values aren't actually
  granted until the modal is submitted. Only affects `approve_form.php`;
  the separate `table:granted*` strings used for already-decided
  historical values elsewhere are unchanged.

## [0.11] - 2026-09-07

### Added

- `notification_manager::send_new_request()`, called from
  `request_manager::create_request()`: notifies everyone holding
  `local/quizextensionmanager:manage` for the quiz when a student submits
  a new request, via a new `newrequest` message provider
  (`db/messages.php`) and admin-configurable subject/body templates
  matching the existing approved/denied pattern.

### Changed

- `settings.php` and `quiz_settings_form.php`: the allowed-documentation-
  file-types field now uses Moodle's standard `filetypes`
  picker widget (`admin_setting_filetypes` / the `filetypes` form
  element) instead of a bare text box. This was a TODO left over from the
  very first scaffold, confirmed as needed while testing the
  documentation-upload workflow on a live site.

## [0.10] - 2026-09-07

### Fixed

- `request_manager::get_documentation_html()`: links now open in a new tab
  (`target="_blank" rel="noopener"`) instead of the same tab, so opening a
  large image/PDF from the approve/deny modal doesn't navigate the
  reviewer away from `manage.php` mid-review.

## [0.9] - 2026-09-07

### Added

- `classes/request_manager.php`: `get_documentation_html()`, building
  download links for a request's uploaded supporting documentation.
- Wired into `approve_form.php` and `deny_form.php`, so a teacher
  reviewing a request can now actually see what was uploaded -- confirmed
  as a real gap while testing the AJAX approve/deny workflow (the
  student-side upload existed with no corresponding way to view it).

### Fixed

- README: documented that `amd/build/*.min.js` must be committed to this
  repository like any other plugin file, after a real incident where the
  built AMD module only ever existed in a deployed copy and was silently
  deleted by a subsequent `rsync --delete`-based sync, breaking the
  approve/deny modals with "Uncaught Error: No define call for
  local_quizextensionmanager/manage" until rebuilt.

## [0.8] - 2026-09-07

### Fixed

- `myrequests.php`: called a bare `require_login()` even when a `cmid` was
  present, instead of `require_login($course, false, $cm)` like every
  other page in the plugin. This left `$PAGE->course` set to the site
  while `$PAGE->set_context()` was given the quiz's real module context --
  a real bug hit on a live site, throwing "Coding error detected... The
  course you passed to $PAGE->set_cm does not correspond to the $cm."
  right after a student submitted a request and landed on this page.

## [0.3.3] - 2026-09-07

### Fixed

- `classes/output/extension_link.php`: no longer shows the student-facing
  "Request extension" entry point (or its pending/view-requests variants)
  on the quiz view page to anyone holding
  `local/quizextensionmanager:manage` in that context. Site admins bypass
  normal capability checks entirely, so a teacher/admin viewing the quiz
  page was seeing the student-facing link too.

## [0.3.2] - 2026-09-07

### Changed

- `request_form.php`: replaced the free-text "requested new number of
  attempts" field, which was ambiguous between "the new total" and "how
  many extra", with a yes/no checkbox ("I need an additional attempt at
  this quiz"). `request.php` converts it to a total (current + 1) before
  storing, so `requestedattempts` still means "new total attempts"
  everywhere downstream -- no schema or `request_manager`/`override_manager`
  changes. Hidden entirely when the quiz already allows unlimited
  attempts.
- `approve_form.php`: now shows the quiz's current attempts total
  alongside the (unchanged, still an absolute-total) granted-attempts
  field, so the teacher can see the actual difference themselves.
- Retired the now-unused `form:requestedattempts` /
  `form:requestedattempts_help` lang strings; added
  `form:needsadditionalattempt`.

## [0.3.1] - 2026-09-07

### Fixed

- The requested/granted time limit fields (`request_form.php`,
  `approve_form.php`) now default to the quiz's current time limit while
  unchecked, instead of a bare `0` -- flagged from testing on a real site
  as reading confusingly like "requesting a 0-minute limit". Purely a
  display fix: the `duration` form element already submits `0` whenever
  its checkbox is unchecked regardless of the displayed number, and that
  `0` is (and was already) correctly treated as "no change requested"
  downstream, so this changes nothing about actual behaviour.

## [0.3.0] - 2026-09-07

### Added

- AJAX approve/deny for the per-quiz pending-requests dashboard
  (`manage.php`), per `PLUGIN_SPEC.md` v0.4: `classes/form/approve_form.php`
  and `classes/form/deny_form.php` (both `\core_form\dynamic_form`
  subclasses) and `amd/src/manage.js`, using Moodle's stock
  `core_form/modalform` + `core_form_dynamic_form` webservice -- no custom
  AJAX endpoints of our own. Approving or denying now happens in a modal;
  the acted-on row is removed from the table client-side and a toast
  confirms the outcome, with no full page reload.

### Changed

- `manage.php`: the pending-requests table's per-row action is now
  `Approve`/`Deny` buttons that open the modals above, instead of a
  `Review` link to a full-page form.
- `tests/behat/approval_workflow.feature`: updated to drive the new modal
  UI and tagged `@javascript` (a real browser driver is required now that
  this flow depends on JS).

### Removed

- `classes/form/approval_form.php`: superseded by `approve_form.php` /
  `deny_form.php`.

## [0.2.1] - 2026-09-07

### Changed

- `classes/override_manager.php`: rewritten to delegate to mod_quiz's own
  `\mod_quiz\local\override_manager::save_override()` (verified against the
  real Moodle 5.2.2 core source) instead of writing to `{quiz_overrides}`
  directly. Also fixes a data-loss risk the naive version of this delegation
  would have introduced: an existing override's `timeopen`/`password` are
  now carried forward on update so approving a request never resets fields
  this plugin doesn't manage. See the README's "`quiz_overrides`
  integration" section.
- `db/messages.php`: fixed use of `MESSAGE_DEFAULT_LOGGEDIN` /
  `MESSAGE_DEFAULT_LOGGEDOFF`, which do not exist in Moodle 5.2.2 (a fatal
  PHP 8 `Error` on an undefined constant), replaced with the correct
  `MESSAGE_DEFAULT_ENABLED`.

### Added

- `tests/behat/request_workflow.feature`, `approval_workflow.feature`,
  `settings.feature`: Behat coverage for the main UI flows -- a student
  submitting/editing/cancelling a request and being blocked from a
  duplicate pending one, a teacher approving (verified via the native
  "User overrides" page) or denying a request, and a teacher toggling
  per-quiz extension requests on/off.

## [0.2.0] - 2026-09-06

### Added

- `classes/eligibility.php` / `classes/eligibility_result.php`: full
  eligibility checks (per-quiz enabled flag, quiz open state, request
  window per `requestwindowtype`, duplicate-pending prevention, course
  quota counted on approved requests only).
- `classes/request_manager.php`: request CRUD and state transitions
  (create/update/cancel/approve/deny), plus read helpers used by the
  student and teacher pages.
- `classes/override_manager.php`: applies an approved request to
  `{quiz_overrides}`, writing only fields that deviate from the quiz's own
  settings, updating rather than duplicating the single per-user override
  row. See the README note below on manual verification against real
  Moodle 5.2 core.
- `classes/notification_manager.php` and `db/messages.php`: approved/denied
  message-API notifications with admin-configurable templates and
  built-in defaults.
- `classes/form/request_form.php`, `approval_form.php`,
  `quiz_settings_form.php`, `course_settings_form.php`.
- `classes/table/requests_table.php`: course-wide report table.
- Top-level pages: `request.php`, `myrequests.php`, `manage.php`,
  `report.php`, `quizsettings.php`, `coursesettings.php`.
- `local_quizextensionmanager_extend_settings_navigation()` in `lib.php`,
  adding capability-gated navigation entries for the manage/settings and
  report/course-settings pages.
- `classes/output/extension_link.php` and `local_quizextensionmanager_pluginfile()`
  fully implemented (previously stubs).
- PHPUnit coverage: `tests/eligibility_test.php`,
  `tests/request_manager_test.php`, `tests/override_manager_test.php`.
- Many new language strings for the above.

## [0.1.0] - 2026-09-06

### Added

- Initial plugin scaffold: `version.php`, `db/install.xml` (request,
  per-quiz and per-course settings tables), `db/access.php` capabilities
  (`request`, `manage`), `db/upgrade.php` stub.
- Full privacy provider (`classes/privacy/provider.php`) covering the
  request table and the `documentation` file area.
- `lib.php` with a `local_quizextensionmanager_pluginfile()` stub for
  serving documentation uploads.
- `settings.php` with site-wide defaults (max extension, reason required,
  documentation mode, allowed file types, notification templates).
- `classes/output/extension_link.php` stub, the integration point used by
  `quizaccess_quizextensionmanager`.
- Smoke test, README, and initial language strings.
