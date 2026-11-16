# Roadmap

Candidate improvements that are **not yet part of `PLUGIN_SPEC.md`**. Items
here are things worth doing, flagged from actually using the plugin, but
not yet triaged into a spec version. When one gets picked up for
implementation, it should be written into `PLUGIN_SPEC.md` (with a version
bump and a Version History entry) at the same time it's built, then removed
from this file.

## Proposed

### Back up / restore / copy the plugin's settings with the course (and shift dates)

Flagged: 2026-11-15, from the Math1145 redeploy question ("does Moodle's
date adjustment also adjust the extension cutoff dates?"). The user's
semester workflow is a course copy or restore with a new start date.

Today neither `local_quizextensionmanager` nor
`quizaccess_quizextensionmanager` has any backup/restore code (no
`backup/` directory in either), so a course copy brings across **none** of
the plugin's data: per-quiz settings (`quizextensionmanager_quizset`) and
per-course settings (`quizextensionmanager_crsset`) silently revert to
defaults, and have to be re-entered quiz by quiz. The date the user cares
about most -- the **cutoff for allowing extension requests** -- is only a
stored absolute timestamp for the `fixed-date` window type
(`quizset.requestwindowdate`). The default `days-after-close` window is
computed live from the quiz's own `timeclose` (`eligibility.php:
get_request_window_end()`), so it already follows Moodle's date shift for
free; `until-course-end` follows the course's `enddate`.

Goal: a course copy/restore should carry the settings across and shift any
stored date by the same offset Moodle applies to the rest of the course.

Mechanism (confirmed in core source, not yet tried):

- **Per-quiz settings:** core's quiz backup already calls
  `add_subplugin_structure('quizaccess', $quiz, true)` (and the matching
  restore call), so the natural home is a
  `backup/moodle2/backup_quizaccess_quizextensionmanager_subplugin.class.php`
  / `restore_quizaccess_quizextensionmanager_subplugin.class.php` pair in
  the access-rule plugin, carrying the `quizset` row. (Alternative: a
  `local` plugin backup class -- core also calls `add_plugin_structure(
  'local', $module, true)` for activities -- but the access rule is the
  more idiomatic fit for quiz-specific data.)
- **Per-course settings:** `crsset` needs the course-level equivalent, via
  `add_plugin_structure('local', $course, ...)`, i.e.
  `backup_local_quizextensionmanager_plugin` /
  `restore_local_quizextensionmanager_plugin` in the local plugin.
- **Date shifting:** in the restore class, run `requestwindowdate` through
  `$this->apply_date_offset()` (core's `restore_step::apply_date_offset()`),
  which is what applies the new-start-date offset on a copy/restore.
- **Request rows:** deliberately *not* copied, since old students' requests
  shouldn't follow a course into a new semester. (Worth confirming and
  documenting; only revisit if someone wants an archive.)
- `quizoverrideid` on any request rows would also need remapping if
  requests were ever included -- another reason to leave them out.

Also needs: new backup/restore PHPUnit coverage (Moodle's
`backup_restore_base_testcase`-style round trip) and a Behat course
copy scenario if practical; note student `quiz_overrides` themselves are
core data and only come across when the copy includes user data.

Flagged: 2026-11-15, from real usage: many students request 2 attempts
when they should only be given 1.

`approve_form.php` currently shows only the quiz's own attempts limit
(`form:currentattempts`) next to the editable granted-attempts field --
nothing about what *this student* has already used. Sometimes a request
is legitimate (the student started an attempt but never finished it and
needs a new one), and the teacher can't tell without leaving the modal.
Show, for the requesting student on that quiz: how many attempts they
have started, how many are finished vs. still in progress/abandoned, and
ideally each attempt's state and grade/score (read from
`{quiz_attempts}`, e.g. via `quiz_get_user_attempts()`), so the teacher
can see at a glance whether "I need a second attempt" is justified. Also
worth considering: surfacing the same summary on the request form itself
so students see what they've already used before asking.

### Show the current time limit on the Approve modal, and let teachers disable time-limit requests

Flagged: 2026-11-15, from real usage: on a set of quizzes configured with
no time limit, students keep entering a time limit anyway (sometimes
absurdly short ones), and the teacher has to strip them out by hand.

Two parts:

1. **Display.** `approve_form.php` has an editable "Requested time limit"
   field but never shows the quiz's *current* time limit (only
   `request_form.php` does, via `form:currenttimelimit`). Show it, as the
   modal already does for the current attempts limit, so the teacher sees
   at a glance whether a requested limit is a change from "no limit".
2. **Disable switch.** Add a way to stop students requesting time-limit
   changes at all -- both a per-quiz override (`quizextensionmanager_quizset`)
   and a course-wide default (`quizextensionmanager_crsset`), following the
   same quiz -> course -> site fallback pattern `requestwindowdays` already
   uses. Needs new columns with a `db/upgrade.php` step and matching
   fields on `quiz_settings_form.php` / `course_settings_form.php`. When
   disabled, `request_form.php` should hide the time-limit field, and
   `eligibility`/`request_manager::create_request()` should ignore any
   submitted `requestedtimelimit` rather than trusting the form. Worth
   considering the same switch for the attempts field, since it has the
   same "students ask for things they shouldn't get" problem. A related
   cheaper safeguard: don't offer the time-limit field at all when the
   quiz has no limit (a time limit can't be "extended" from nothing).

### Offer a "requested start date" option alongside the close date

Flagged: 2026-11-15, from real usage. The current close-date-only design
suits a "take anytime" quiz, but another site (or a future use here) may
need a student to request a later *start* (open) date instead -- e.g.
they were absent or enrolled late. `quiz_overrides` supports `timeopen`
natively, and `override_manager` already carries an existing override's
`timeopen` forward on update, so the plumbing is mostly there. Needs: a
`requestedtimeopen`/`grantedtimeopen` pair on the request table (with a
`db/upgrade.php` step), an optional start-date field on `request_form.php`
and `approve_form.php`, and likely a per-quiz or site-wide setting
choosing which of close date / start date (or both) a request may
change, so a site like this one can keep exposing only the close date.

### Rethink "Maximum extension allowed" -- wrong unit, and unenforced

Flagged: 2026-09-12, from real usage: "It's unusual for a student to
request additional minutes on the quiz." On reflection, the more
important setting is a cap on **days**, not minutes: replace
`local_quizextensionmanager/maxextension` (currently minutes, 0 = no
limit) with a `maxextensiondays`-style setting capping how far
`requestedtimeclose` (the new close date/time) can move out. That maps
naturally to how a close-date extension is actually requested/thought
about ("a few extra days"), unlike a flat minutes figure.

Also currently **unenforced** regardless of unit -- `settings.php`
already has a TODO next to it ("decide final units/validation... and
enforce this cap when requests are approved"), and grepping the codebase
confirms `maxextension` is defined nowhere else; nothing in
`eligibility.php` or `request_manager.php` reads it. Whatever the final
setting is named, it needs to actually be validated in `eligibility.php`
before a request is allowed to be submitted.

See also the deferred idea below about a separate, multiplier-based cap
specifically for `requestedtimelimit` (the quiz's own time limit).

### Investigate a "Settings" link on the Plugins overview page

Flagged: 2026-09-12, from installing on a real production site. Other
plugins (e.g. the `qtype_formulas` and `qtype_stack` question types) show
a direct "Settings" link next to their row on *Site administration ->
Plugins -> Plugins overview -> Additional plugins*, rather than making
the admin hunt for the settings page through the category tree. Worth
checking whether `local_quizextensionmanager` (and/or the
`quizaccess_quizextensionmanager` subplugin, which currently has no
settings page at all) can get the same treatment, and if so what the
actual mechanism is -- likely tied to how each plugin type's
`\core\plugininfo\*` class resolves a matching admin settings/external
page by component name, which our `settings.php` may or may not already
satisfy (it registers `admin_settingpage('local_quizextensionmanager',
...)`, i.e. already named after the component -- unclear yet whether
that's sufficient or something else is needed).

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

### Multiplier-based cap on requested time limit extensions

Flagged: 2026-09-12, alongside the `maxextension` rethink above.
**Explicitly deferred by choice**: "Not for now, but for future
consideration."

For `requestedtimelimit` (extending the quiz's own time limit -- distinct
from `requestedtimeclose`, the close-date extension the day-based cap
above governs), a flat number of extra minutes is a poor fit for the
most common real-world reason this gets requested: documented
accommodations, which are conventionally expressed as a multiplier of
normal time (e.g. "time and a half"/1.5x, or double time/2x), not a fixed
number of minutes. A future site-wide setting like
`maxtimelimitmultiplier` (e.g. `2.0`) would cap `requestedtimelimit` at
that multiple of the quiz's own `timelimit`, rather than (or perhaps in
addition to) a flat-minutes cap.

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
