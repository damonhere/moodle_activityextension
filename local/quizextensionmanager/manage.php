<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Teacher-facing pending-requests dashboard for a single quiz.
 *
 * Approve/deny happens in a modal via AJAX (see PLUGIN_SPEC.md v0.4,
 * classes/form/approve_form.php, classes/form/deny_form.php, and
 * amd/src/manage.js) -- this page never navigates away for those actions,
 * it only removes the acted-on row client-side.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_quizextensionmanager\request_manager;

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('local/quizextensionmanager:manage', $context);

$PAGE->set_url('/local/quizextensionmanager/manage.php', ['cmid' => $cmid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$title = get_string('page:manage', 'local_quizextensionmanager');
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);

$PAGE->requires->js_call_amd('local_quizextensionmanager/manage', 'init');

$pending = request_manager::get_pending_requests($cm->instance);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$emptyattributes = ['id' => 'local-quizextensionmanager-empty', 'class' => 'alert alert-info'];
if (!empty($pending)) {
    $emptyattributes['hidden'] = 'hidden';
}
echo html_writer::div(get_string('nopendingrequests', 'local_quizextensionmanager'), '', $emptyattributes);

$tableattributes = ['id' => 'local-quizextensionmanager-pending-table'];
if (empty($pending)) {
    $tableattributes['hidden'] = 'hidden';
}
echo html_writer::start_tag('div', $tableattributes);

$table = new html_table();
$table->head = [
    get_string('table:student', 'local_quizextensionmanager'),
    get_string('table:requestedtimeclose', 'local_quizextensionmanager'),
    get_string('table:requestedtimelimit', 'local_quizextensionmanager'),
    get_string('table:requestedattempts', 'local_quizextensionmanager'),
    get_string('table:reason', 'local_quizextensionmanager'),
    get_string('table:actions', 'local_quizextensionmanager'),
];

foreach ($pending as $record) {
    $student = core_user::get_user($record->userid);

    $actions = html_writer::link(
        '#',
        get_string('action:approve', 'local_quizextensionmanager'),
        ['data-action' => 'approve-request', 'data-requestid' => $record->id, 'class' => 'btn btn-sm btn-primary mr-1']
    ) . html_writer::link(
        '#',
        get_string('action:deny', 'local_quizextensionmanager'),
        ['data-action' => 'deny-request', 'data-requestid' => $record->id, 'class' => 'btn btn-sm btn-secondary']
    );

    $row = new html_table_row([
        fullname($student),
        !empty($record->requestedtimeclose) ? userdate($record->requestedtimeclose) : '-',
        !empty($record->requestedtimelimit) ? format_time($record->requestedtimelimit) : '-',
        $record->requestedattempts ?? '-',
        format_text((string) $record->reason, FORMAT_PLAIN),
        $actions,
    ]);
    $row->attributes['data-requestid'] = $record->id;

    $table->data[] = $row;
}

echo html_writer::table($table);
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
