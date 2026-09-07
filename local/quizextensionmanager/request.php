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
 * Student-facing new/edit extension request page.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

use local_quizextensionmanager\eligibility;
use local_quizextensionmanager\request_manager;
use local_quizextensionmanager\form\request_form;

$cmid = optional_param('cmid', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);

if ($id) {
    $requestrecord = request_manager::get_request($id);
    $cm = get_coursemodule_from_instance('quiz', $requestrecord->quizid, 0, false, MUST_EXIST);
} else {
    $requestrecord = null;
    $cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
}

$course = get_course($cm->course);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('local/quizextensionmanager:request', $context);

if ($requestrecord && (int) $requestrecord->userid !== (int) $USER->id) {
    throw new moodle_exception('error:notowner', 'local_quizextensionmanager');
}
if ($requestrecord && $requestrecord->status !== 'pending') {
    throw new moodle_exception('error:notpending', 'local_quizextensionmanager');
}

$pageparams = $id ? ['id' => $id] : ['cmid' => $cmid];
$PAGE->set_url('/local/quizextensionmanager/request.php', $pageparams);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$title = get_string($id ? 'page:editrequest' : 'page:newrequest', 'local_quizextensionmanager');
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('pluginname', 'local_quizextensionmanager'));
$PAGE->navbar->add($title);

if (!$requestrecord) {
    $eligibilityresult = eligibility::can_request($quiz->id, $USER->id);
    if (!$eligibilityresult->allowed) {
        redirect(
            new moodle_url('/mod/quiz/view.php', ['id' => $cm->id]),
            $eligibilityresult->get_reason_string(),
            null,
            \core\notification::ERROR
        );
    }
}

$quizset = $DB->get_record('quizextensionmanager_quizset', ['quizid' => $quiz->id]);
$documentationmode = $quizset->documentationmode ?? get_config('local_quizextensionmanager', 'documentationmode');
$allowedtypes = $quizset->allowedfiletypes ?? get_config('local_quizextensionmanager', 'allowedfiletypes');

$filemanageroptions = [
    'subdirs' => 0,
    'maxfiles' => 5,
    'accepted_types' => (!empty($allowedtypes) && trim((string) $allowedtypes) !== '') ? explode(' ', trim($allowedtypes)) : '*',
];

$customdata = [
    'cmid' => $cm->id,
    'quiz' => $quiz,
    'quizset' => $quizset,
    'requestid' => $requestrecord->id ?? 0,
    'filemanageroptions' => $filemanageroptions,
];

$mform = new request_form(null, $customdata);

$formdata = new stdClass();
$formdata->cmid = $cm->id;

if ($requestrecord) {
    $formdata->id = $requestrecord->id;
    $formdata->requestedtimeclose = $requestrecord->requestedtimeclose ?: 0;
    $formdata->requestedtimelimit = $requestrecord->requestedtimelimit ?: $quiz->timelimit;
    $formdata->needsadditionalattempt = !empty($requestrecord->requestedattempts)
        && (int) $requestrecord->requestedattempts > (int) $quiz->attempts;
    $formdata->reason = $requestrecord->reason;

    if ($documentationmode !== 'none') {
        $formdata = file_prepare_standard_filemanager(
            $formdata,
            'documentation',
            $filemanageroptions,
            $context,
            'local_quizextensionmanager',
            'documentation',
            $requestrecord->id
        );
    }
}

$mform->set_data($formdata);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/quizextensionmanager/myrequests.php', ['cmid' => $cm->id]));
} else if ($data = $mform->get_data()) {
    // The form only asks a yes/no question ("I need an additional
    // attempt") to avoid the ambiguity of a raw number (new total vs. how
    // many extra); convert it to the "new total attempts" value the rest
    // of the plugin (and quiz_overrides itself) works in terms of.
    $fielddata = [
        'requestedtimeclose' => !empty($data->requestedtimeclose) ? (int) $data->requestedtimeclose : null,
        'requestedtimelimit' => !empty($data->requestedtimelimit) ? (int) $data->requestedtimelimit : null,
        'requestedattempts' => !empty($data->needsadditionalattempt) ? ((int) $quiz->attempts + 1) : null,
        'reason' => $data->reason ?? '',
    ];

    if ($requestrecord) {
        $record = request_manager::update_request($requestrecord->id, $USER->id, $fielddata);
    } else {
        $record = request_manager::create_request($quiz->id, $USER->id, $fielddata);
    }

    if ($documentationmode !== 'none' && isset($data->documentation_filemanager)) {
        file_postupdate_standard_filemanager(
            $data,
            'documentation',
            $filemanageroptions,
            $context,
            'local_quizextensionmanager',
            'documentation',
            $record->id
        );
    }

    redirect(
        new moodle_url('/local/quizextensionmanager/myrequests.php', ['cmid' => $cm->id]),
        get_string('notify:requestsaved', 'local_quizextensionmanager'),
        null,
        \core\notification::SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$mform->display();
echo $OUTPUT->footer();
