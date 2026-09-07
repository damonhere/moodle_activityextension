# Changelog

All notable changes to `quizaccess_quizextensionmanager` are documented here.

## [0.1.0] - 2026-09-06

### Added

- Initial plugin scaffold: `version.php` (depends on
  `local_quizextensionmanager`), `rule.php` extending
  `quiz_access_rule_base` with `description()` delegating to
  `local_quizextensionmanager\output\extension_link`.
- Null privacy provider (this plugin stores no data of its own).
- Smoke test, README, and initial language strings.
