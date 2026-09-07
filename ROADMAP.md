# Roadmap

Candidate improvements that are **not yet part of `PLUGIN_SPEC.md`**. Items
here are things worth doing, flagged from actually using the plugin, but
not yet triaged into a spec version. When one gets picked up for
implementation, it should be written into `PLUGIN_SPEC.md` (with a version
bump and a Version History entry) at the same time it's built, then removed
from this file.

## Proposed

### Show teachers a pending-request count + link on the quiz view page

Flagged: 2026-09-07, alongside the "hide from teachers" fix (now done, see
`PLUGIN_SPEC.md` v0.7).

For a user holding `local/quizextensionmanager:manage` in the quiz's module
context, `mod/quiz/view.php` should show a small indicator of how many
pending extension requests exist for that quiz (e.g. "3 pending extension
requests"), linking directly to that quiz's `manage.php` dashboard. This
gives teachers a low-friction way to notice requests need attention without
already knowing to look for the "Extension requests" link in the activity
administration menu.

`extension_link::render()` already branches early to hide the student view
entirely from anyone holding `:manage` -- this item would replace that
early `return '';` with the teacher-facing count/link instead.
