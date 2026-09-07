# Changelog

All notable changes to `local_quizextensionmanager` are documented here.

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
