# Roadmap

Candidate improvements that are **not yet part of `PLUGIN_SPEC.md`**. Items
here are things worth doing, flagged from actually using the plugin, but
not yet triaged into a spec version. When one gets picked up for
implementation, it should be written into `PLUGIN_SPEC.md` (with a version
bump and a Version History entry) at the same time it's built, then removed
from this file.

## Proposed

### Show the reviewer's comment on the teacher-facing course-wide report too

Flagged: 2026-09-12, discovered while fixing the same gap on `myrequests.php`
(the student-facing page, now fixed -- see `PLUGIN_SPEC.md`'s next version).

`classes/table/requests_table.php` (used by `report.php`) shows a
"Reviewer" column (who reviewed it) but never the reviewer's actual
comment/reason text, the same gap `myrequests.php` just had. Add a
`table:reviewreason` column there too, same as was just added to
`myrequests.php`.

### Course-level default for allowed documentation file types

Flagged: 2026-09-07, from testing on a real site.

"Allowed documentation file types" is currently only configurable per-quiz
(`quizextensionmanager_quizset.allowedfiletypes`, falling back to the
site-wide default). For a course with many quizzes, that means setting the
same file types over and over. `requestwindowdays` already has exactly the
two-tier pattern this should follow: a per-quiz value that, when unset,
falls back to a per-*course* default (`quizextensionmanager_crsset.
defaultrequestwindowdays`) before finally falling back to the site-wide
setting. Allowed file types should work the same way.

Needs a schema change: a new `allowedfiletypes` column on
`quizextensionmanager_crsset` (with a `db/upgrade.php` step), plus a field
on `course_settings_form.php` using the same `filetypes` element added to
the per-quiz form in v0.11, and updating `eligibility`/`request_form.php`'s
fallback chain (quiz -> course -> site) to match how the request window
already resolves.

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

### Inconsistent "Enable" checkbox position on the student request form

Flagged: 2026-09-07, from testing on a real site.

On `request_form.php`, the "Requested new close date" field's optional
"Enable" checkbox renders on the **left** of the date selector, but the
"Requested new time limit" field's "Enable" checkbox renders on the
**right** of its number/unit inputs. Both are Moodle form elements
(`date_time_selector` and `duration` respectively) used with
`optional => true`, and each element type appears to lay out its own
"enabled" checkbox in a different default position -- this isn't something
our code explicitly controls today.

Should be made consistent (checkbox on the left for both, most likely),
which will need investigating whether formslib exposes an option to
control checkbox placement for these element types, or whether it needs a
small CSS/template override in this plugin instead.

### Color-code the status column everywhere it's shown

Flagged: 2026-09-07, from testing on a real site.

The Status column is currently plain text (whatever
`get_string('status:' . $status, ...)` returns) everywhere it appears.
Suggested improvement: render it as a colored badge/box instead, so status
is recognizable at a glance -- yellow for Pending, green for Approved, red
for Denied, orange for Cancelled. Confirmed in scope for all three places
this plugin shows a status:

- `myrequests.php` (student "My extension requests" page)
- `classes/table/requests_table.php`'s `col_status()` (the teacher-facing
  course-wide report, `report.php`)
- `manage.php` (the teacher's pending-requests dashboard) -- every row
  there is Pending by definition, so this would mainly mean styling that
  one status consistently with how it's colored elsewhere, for visual
  consistency across the three pages rather than to distinguish rows from
  each other.

Likely implemented with Bootstrap's existing badge classes (e.g.
`badge badge-warning`/`badge-success`/`badge-danger` and something
orange-ish for cancelled, which doesn't have as direct a stock Bootstrap
class) rather than bespoke CSS, to stay consistent with the rest of
Moodle's theme, and probably worth a small shared helper (e.g.
`request_manager::status_badge($status)`) so the three call sites don't
each reimplement the same status-to-class mapping.

### Show a per-quiz history of decided requests, not just pending ones

Flagged: 2026-09-07, from testing on a real site. Explicitly flagged for a
future release, not to be built now.

`manage.php` only ever shows `status = 'pending'` requests for a quiz, by
design -- once a request is approved/denied/cancelled it disappears from
that view entirely. The only place to see a decided request afterward is
the course-wide `report.php`, which mixes in every other quiz in the
course too. A teacher focused on one quiz has no quiz-scoped way to look
back at what was already approved/cancelled for it.

Possible direction: a collapsed "History" section (or a status filter)
below the pending list on `manage.php`, scoped to that one quiz, reusing
`request_manager::get_course_requests()`-style querying but filtered by
`quizid` instead of `courseid`.

## Under consideration (deferred)

Bigger, less-settled ideas -- deliberately not acted on yet, pending real
usage experience.

### Consolidate the teacher-side report and per-quiz manage page

Flagged: 2026-09-07, from testing on a real site. **Deferred by choice**:
"leave it for now to see how it works" before deciding whether to act.

The teacher-side extension-management surface is split across two pages
today: `report.php` (course-wide, read-mostly, one row per request across
every quiz) and `manage.php` (per-quiz, actionable -- AJAX approve/deny via
`core_form/modalform`, but only for that one quiz's pending requests). This
is a bit clunky: a teacher can see the full picture on the report page but
has to jump to a specific quiz's dashboard to actually act on anything.

Ideal direction, if this friction turns out to matter in practice: bring
the same AJAX approve/deny modals (`approve_form`/`deny_form`) directly
into `report.php`'s per-row actions for pending requests, so acting on a
request doesn't require leaving the course-wide report at all -- which
could ultimately make `manage.php` redundant, or at least less necessary
as the primary place teachers work from.
