# Roadmap

Candidate improvements that are **not yet part of `PLUGIN_SPEC.md`**. Items
here are things worth doing, flagged from actually using the plugin, but
not yet triaged into a spec version. When one gets picked up for
implementation, it should be written into `PLUGIN_SPEC.md` (with a version
bump and a Version History entry) at the same time it's built, then removed
from this file.

## Proposed

### Hide "Request extension" from teachers on the quiz view page

Flagged: 2026-09-07, from testing on a real site.

`local_quizextensionmanager\output\extension_link::render()` (called from
`quizaccess_quizextensionmanager`'s `description()` hook) currently shows
the "Request extension" entry point to anyone holding
`local/quizextensionmanager:request` in the quiz's module context. Site
admins bypass normal capability checks entirely (`has_capability()` returns
true for them regardless of the capability's declared archetypes), so a
teacher/admin account viewing the quiz page sees the student-facing link
too — reading as confusing or wrong on what should be a teacher-facing view
of the page.

Proposed fix: in `extension_link::render()`, don't show the student-facing
link/status at all to a user who holds `local/quizextensionmanager:manage`
in that context, regardless of whether they also technically pass the
`:request` capability check.

### Show teachers a pending-request count + link on the quiz view page

Flagged: 2026-09-07, alongside the item above.

For a user holding `local/quizextensionmanager:manage` in the quiz's module
context, `mod/quiz/view.php` should show a small indicator of how many
pending extension requests exist for that quiz (e.g. "3 pending extension
requests"), linking directly to that quiz's `manage.php` dashboard. This
gives teachers a low-friction way to notice requests need attention without
already knowing to look for the "Extension requests" link in the activity
administration menu.

Since both of these items touch the same rendering function
(`extension_link::render()`), it makes sense to implement them together:
branch early on whether the current user holds `:manage` (show the teacher
view: pending count + dashboard link) vs. `:request` (show the existing
student view), rather than trying to show both to the same user.
