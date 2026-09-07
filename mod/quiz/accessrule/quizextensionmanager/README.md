# quizaccess_quizextensionmanager

Thin quiz access rule subplugin whose sole job is to place the "Request
extension" entry point on `mod/quiz/view.php`, using the quiz access rule
`description()` hook -- Moodle's own sanctioned mechanism for adding
server-rendered content to that page before an attempt starts (the same
mechanism used for password/access-rule messages there).

This plugin does **not** gate or restrict quiz attempts: it does not
override `prevent_access()` or any other gating method. All actual logic
(eligibility checks, the request form, the approval dashboard, notifications,
and override-writing) lives in the companion
[`local_quizextensionmanager`](../../../../local/quizextensionmanager) plugin,
which this plugin depends on and delegates to via
`\local_quizextensionmanager\output\extension_link::render()`.

**Status:** initial scaffold only. `rule.php` currently delegates to a stub
that renders nothing -- see `TODO` markers.

## Installation

Requires `local_quizextensionmanager` to be installed first (declared as a
hard dependency in `version.php`).

1. Copy (or symlink) this directory to
   `mod/quiz/accessrule/quizextensionmanager` in your Moodle codebase.
2. Visit **Site administration > Notifications** to run the installer, or
   run:
   ```bash
   php admin/cli/upgrade.php --non-interactive
   ```
