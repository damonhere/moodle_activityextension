# Changelog

All notable changes to `quizaccess_quizextensionmanager` are documented here.

## [0.1.1] - 2026-09-07

### Fixed

- `rule.php` extended a class named `quiz_access_rule_base`, which does not
  exist in Moodle 5.2 -- confirmed against the real 5.2.2 core source that
  the base class was renamed/namespaced to `\mod_quiz\local\access_rule_base`
  at some point before 5.2, with no backward-compatible alias. This was a
  fatal "Class not found" error on any page that loads quiz access rules
  (i.e. every quiz view page), caught when actually running the plugin on a
  real Moodle 5.2.2 site for the first time. Fixed to extend
  `\mod_quiz\local\access_rule_base` and use the real `quiz_settings` type
  hint on `make()`, matching the real `quizaccess_password` subplugin's
  convention.

## [0.1.0] - 2026-09-06

### Added

- Initial plugin scaffold: `version.php` (depends on
  `local_quizextensionmanager`), `rule.php` extending
  `quiz_access_rule_base` with `description()` delegating to
  `local_quizextensionmanager\output\extension_link`.
- Null privacy provider (this plugin stores no data of its own).
- Smoke test, README, and initial language strings.
