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
 * Teacher-facing pending-requests dashboard for a single quiz, with an
 * inline approve/deny detail view.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_quizextensionmanager\request_manager;
use local_quizextensionmanager\form\approval_form;

$cmid = required_param('cmid', PARAM_INT);
$requestid = optional_param('requestid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('local/quizextensionmanager:manage', $context);

$pageparams = ['cmid' => $cmid];
if ($requestid) {
    $pageparams['requestid'] = $requestid;
}
$PAGE->set_url('/local/quizextensionmanager/manage.php', $pageparams);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$title = get_string('page:manage', 'local_quizextensionmanager');
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);

if ($requestid) {
    $record = request_manager::get_request($requestid);
    if ((int) $record->quizid !== (int) $cm->instance) {
        throw new moodle_exception('invalidrecord', 'error');
    }

    $manageurl = new moodle_url('/local/quizextensionmanager/manage.php', ['cmid' => $cm->id]);

    if ($record->status !== 'pending') {
        // Already actioned (e.g. reloaded/back button) -- just show the queue again.
        redirect($manageurl);
    }

    $mform = new approval_form(null, ['request' => $record, 'cmid' => $cm->id]);

    if ($mform->is_cancelled()) {
        redirect($manageurl);
    } else if ($data = $mform->get_data()) {
        $grantedattempts = trim((string) ($data->grantedattempts ?? ''));

        $granted = [
            'grantedtimeclose' => !empty($data->grantedtimeclose) ? (int) $data->grantedtimeclose : null,
            'grantedtimelimit' => !empty($data->grantedtimelimit) ? (int) $data->grantedtimelimit : null,
            'grantedattempts' => ($grantedattempts !== '') ? (int) $grantedattempts : null,
        ];

        if (!empty($data->approve)) {
            request_manager::approve_request($record->id, $USER->id, $granted, $data->reviewreason ?? '');
            $notice = get_string('notify:approved', 'local_quizextensionmanager');
        } else {
            request_manager::deny_request($record->id, $USER->id, $data->reviewreason ?? '');
            $notice = get_string('notify:denied', 'local_quizextensionmanager');
        }

        redirect($manageurl, $notice, null, \core\notification::SUCCESS);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading($title);
    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

$pending = request_manager::get_pending_requests($cm->instance);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

if (empty($pending)) {
    echo $OUTPUT->notification(get_string('nopendingrequests', 'local_quizextensionmanager'), 'info');
} else {
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
        $reviewurl = new moodle_url('/local/quizextensionmanager/manage.php', ['cmid' => $cm->id, 'requestid' => $record->id]);

        $table->data[] = [
            fullname($student),
            !empty($record->requestedtimeclose) ? userdate($record->requestedtimeclose) : '-',
            !empty($record->requestedtimelimit) ? format_time($record->requestedtimelimit) : '-',
            $record->requestedattempts ?? '-',
            format_text((string) $record->reason, FORMAT_PLAIN),
            html_writer::link($reviewurl, get_string('action:review', 'local_quizextensionmanager')),
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
