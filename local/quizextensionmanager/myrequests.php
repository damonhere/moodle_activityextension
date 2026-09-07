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
 * Student-facing "my requests" listing, with quota display and edit/cancel actions.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_quizextensionmanager\eligibility;
use local_quizextensionmanager\request_manager;

$cmid = optional_param('cmid', 0, PARAM_INT);
$cancelid = optional_param('cancel', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

if ($cmid) {
    $cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
    $course = get_course($cm->course);
    $context = context_module::instance($cm->id);
    // Must pass $course/$cm together here (not a bare require_login()) so
    // $PAGE->course and $PAGE->cm are established consistently before
    // $PAGE->set_context() below sets a module context -- otherwise
    // $PAGE->course stays the site while the context belongs to this
    // quiz's real course, and Moodle's own navigation/activity-header
    // rendering throws "The course you passed to $PAGE->set_cm does not
    // correspond to the $cm." trying to reconcile the two.
    require_login($course, false, $cm);
    require_capability('local/quizextensionmanager:request', $context);
} else {
    $cm = null;
    $course = null;
    $context = context_system::instance();
    require_login();
}

$pageparams = $cmid ? ['cmid' => $cmid] : [];
$PAGE->set_url('/local/quizextensionmanager/myrequests.php', $pageparams);
$PAGE->set_context($context);
$PAGE->set_pagelayout($cmid ? 'incourse' : 'standard');
$title = get_string('page:myrequests', 'local_quizextensionmanager');
$PAGE->set_title($title);
$PAGE->set_heading($cm ? $course->fullname : $title);

$returnurl = new moodle_url('/local/quizextensionmanager/myrequests.php', $pageparams);

if ($cancelid) {
    $record = request_manager::get_request($cancelid);

    if ((int) $record->userid !== (int) $USER->id) {
        throw new moodle_exception('error:notowner', 'local_quizextensionmanager');
    }
    if ($record->status !== 'pending') {
        throw new moodle_exception('error:notpending', 'local_quizextensionmanager');
    }

    if ($confirm && confirm_sesskey()) {
        request_manager::cancel_request($cancelid, $USER->id);
        redirect($returnurl, get_string('notify:requestcancelled', 'local_quizextensionmanager'), null, \core\notification::SUCCESS);
    }

    $confirmurl = new moodle_url('/local/quizextensionmanager/myrequests.php', array_merge($pageparams, [
        'cancel' => $cancelid,
        'confirm' => 1,
        'sesskey' => sesskey(),
    ]));

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('confirm:cancelrequest', 'local_quizextensionmanager'), $confirmurl, $returnurl);
    echo $OUTPUT->footer();
    exit;
}

$requests = request_manager::get_user_requests($USER->id, $cm ? $cm->instance : null);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

if ($cm) {
    $quiz = $DB->get_record('quiz', ['id' => $cm->instance]);
    [$used, $max] = request_manager::get_quota($course->id, $USER->id);
    $maxstr = ($max === null) ? get_string('unlimited', 'local_quizextensionmanager') : $max;

    echo $OUTPUT->notification(
        get_string('quota:used', 'local_quizextensionmanager', (object) ['used' => $used, 'max' => $maxstr]),
        'info'
    );

    $eligibilityresult = eligibility::can_request($quiz->id, $USER->id);
    if ($eligibilityresult->allowed) {
        $newurl = new moodle_url('/local/quizextensionmanager/request.php', ['cmid' => $cm->id]);
        echo $OUTPUT->single_button($newurl, get_string('action:newrequest', 'local_quizextensionmanager'));
    }
}

if (empty($requests)) {
    echo $OUTPUT->notification(get_string('norequests', 'local_quizextensionmanager'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('table:quiz', 'local_quizextensionmanager'),
        get_string('table:status', 'local_quizextensionmanager'),
        get_string('table:requestedtimeclose', 'local_quizextensionmanager'),
        get_string('table:grantedtimeclose', 'local_quizextensionmanager'),
        get_string('table:timecreated', 'local_quizextensionmanager'),
        get_string('table:actions', 'local_quizextensionmanager'),
    ];

    foreach ($requests as $record) {
        $requestquiz = $DB->get_record('quiz', ['id' => $record->quizid]);
        $requestcm = get_coursemodule_from_instance('quiz', $record->quizid, 0, false, IGNORE_MISSING);

        $actions = [];
        if ($record->status === 'pending') {
            $actions[] = html_writer::link(
                new moodle_url('/local/quizextensionmanager/request.php', ['id' => $record->id]),
                get_string('action:edit', 'local_quizextensionmanager')
            );
            $actions[] = html_writer::link(
                new moodle_url('/local/quizextensionmanager/myrequests.php', array_merge(
                    $requestcm ? ['cmid' => $requestcm->id] : [],
                    ['cancel' => $record->id]
                )),
                get_string('action:cancel', 'local_quizextensionmanager')
            );
        }

        $table->data[] = [
            $requestquiz ? format_string($requestquiz->name) : '-',
            get_string('status:' . $record->status, 'local_quizextensionmanager'),
            !empty($record->requestedtimeclose) ? userdate($record->requestedtimeclose) : '-',
            !empty($record->grantedtimeclose) ? userdate($record->grantedtimeclose) : '-',
            userdate($record->timecreated),
            implode(' | ', $actions),
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
