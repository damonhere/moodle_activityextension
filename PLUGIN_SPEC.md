# Moodle Plugin: Quiz Time Extension Requests

Directions for Claude Code to build a Moodle plugin. This file is a starting
skeleton — edit the sections below (especially **Features**) to add, remove,
or refine scope before implementation begins.

## Overview

A Moodle plugin that lets students request a time extension on a quiz
attempt, and lets teachers/instructors review, approve, deny, and manage
those requests. The goal is to replace ad-hoc email/message requests for
extra time with a tracked, in-Moodle workflow.

## Plugin Type

- Likely a **local plugin** (`local_quizextension` or similar) since it needs
  to interact with existing `mod_quiz` data (user overrides) rather than
  being a quiz access rule or sub-plugin.
- Alternative: a **quiz access rule sub-plugin** (`quizaccess_...`) if the
  extension request should be surfaced directly on the quiz attempt page.
- Decide component name and type with Claude Code before scaffolding —
  confirm against Moodle plugin development conventions.

## Toolchain / Skills

This project will use skills from
[SaadRahman01/claude-moodle-dev](https://github.com/SaadRahman01/claude-moodle-dev)
for Moodle plugin scaffolding, coding standards, and testing conventions.
Use those skills for boilerplate (`version.php`, `lang` files, DB install
XML, capabilities, upgrade steps, behat/PHPUnit scaffolding) rather than
hand-rolling them.

## Core Features (draft — edit freely)

### Student-facing
- [ ] Submit a time extension request for a specific quiz attempt/quiz,
      including a reason (free text) and requested extra time.
- [ ] View status of their own requests (pending / approved / denied).
- [ ] Receive a notification (Moodle message/email) when a request is
      approved or denied.
- [ ] Prevent duplicate/overlapping requests for the same quiz.
- [ ] Only allow requests before the quiz close date (or before the
      student's attempt has started, TBD).

### Teacher/instructor-facing
- [ ] View a list of pending extension requests for quizzes in their course.
- [ ] Approve or deny a request, optionally editing the granted extra time.
- [ ] Approving a request automatically creates/updates a **user override**
      on the quiz (extending `timeclose` and/or `timelimit` for that user).
- [ ] Leave a comment/reason when denying a request.
- [ ] Bulk view of all requests across a course (report/table page).
- [ ] Capability-gated: only users with the appropriate capability
      (e.g. `mod/quiz:manageoverrides` or a new custom capability) can
      approve/deny.

### Admin-facing
- [ ] Site-wide settings page (e.g. max extension allowed, whether reason
      is required, notification templates).
- [ ] Capability definitions for who can request vs. approve.

## Data Model (draft)

A new DB table, e.g. `local_quizextension_request`:
- `id`
- `quizid`
- `userid` (student)
- `courseid`
- `requestedminutes` (or requested new close time)
- `reason` (text)
- `status` (pending / approved / denied)
- `reviewerid` (teacher who actioned it)
- `reviewreason` (text, optional)
- `timecreated`
- `timemodified`

## Workflow

1. Student opens quiz (or a dedicated "Request extension" link) and submits
   a request.
2. Teacher sees pending requests (dashboard block, report page, or
   notification).
3. Teacher approves (sets minutes/new close time) or denies (with reason).
4. On approval, plugin writes/updates a `quiz_overrides` row for that
   student for that quiz.
5. Student is notified of the outcome.

## Technical Requirements

- Target Moodle version: _TBD — specify minimum supported version_.
- Follow Moodle coding style (PSR-12-ish + Moodle-specific rules) and use
  `local/quizextension` (or chosen name) directory conventions:
  `version.php`, `db/install.xml`, `db/access.php`, `db/upgrade.php`,
  `lang/en/local_quizextension.php`, `classes/`, `templates/` (Mustache),
  `amd/src/` (JS modules), `externallib.php` or `classes/external/` for AJAX.
- Use Moodle's Forms API (`moodleform`) for the request/approval forms.
- Use Moodle's `\core\notification` / messaging API for status updates.
- Add capabilities in `db/access.php`:
  - `local/quizextension:request` (student)
  - `local/quizextension:manage` (teacher)
- Include PHPUnit tests for core logic (request creation, override
  creation on approval) and Behat tests for the main UI flows.
- Respect Moodle privacy API (GDPR) — implement `\local_quizextension\privacy\provider`
  since the plugin stores personal data (requests, reasons).

## Out of Scope (for now)

- Extensions for assignments or other activity types (quiz-only for v1).
- Automated approval rules (all approvals are manual in v1).

## Open Questions

- Exact plugin frotname/component name.
- Minimum Moodle version to support.
- Should extension apply to `timeclose`, `timelimit`, or both?
- Where does the "request extension" entry point live (quiz view page,
  dashboard block, dedicated report)?
