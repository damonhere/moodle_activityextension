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
 * Per-quiz extension request settings page.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_quizextensionmanager\form\quiz_settings_form;

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('local/quizextensionmanager:manage', $context);

$PAGE->set_url('/local/quizextensionmanager/quizsettings.php', ['cmid' => $cmid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$title = get_string('page:quizsettings', 'local_quizextensionmanager');
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);

$viewurl = new moodle_url('/mod/quiz/view.php', ['id' => $cm->id]);

$mform = new quiz_settings_form(null, ['cmid' => $cm->id]);

$existing = $DB->get_record('quizextensionmanager_quizset', ['quizid' => $cm->instance]);

if ($mform->is_cancelled()) {
    redirect($viewurl);
} else if ($data = $mform->get_data()) {
    $record = $existing ?: new stdClass();
    $record->quizid = $cm->instance;
    $record->enabled = !empty($data->enabled) ? 1 : 0;
    $record->requirereason = !empty($data->requirereason) ? 1 : 0;
    $record->documentationmode = $data->documentationmode;
    $record->allowedfiletypes = $data->allowedfiletypes;
    $record->requestwindowtype = $data->requestwindowtype;

    $days = trim((string) ($data->requestwindowdays ?? ''));
    $record->requestwindowdays = ($days !== '') ? (int) $days : null;
    $record->requestwindowdate = !empty($data->requestwindowdate) ? (int) $data->requestwindowdate : null;

    $now = time();
    $record->timemodified = $now;

    if ($existing) {
        $DB->update_record('quizextensionmanager_quizset', $record);
    } else {
        $record->timecreated = $now;
        $DB->insert_record('quizextensionmanager_quizset', $record);
    }

    redirect($viewurl, get_string('notify:settingssaved', 'local_quizextensionmanager'), null, \core\notification::SUCCESS);
} else if ($existing) {
    $mform->set_data($existing);
} else {
    $mform->set_data((object) [
        'cmid' => $cm->id,
        'enabled' => 1,
        'requirereason' => (int) get_config('local_quizextensionmanager', 'reasonrequired'),
        'documentationmode' => get_config('local_quizextensionmanager', 'documentationmode'),
        'allowedfiletypes' => get_config('local_quizextensionmanager', 'allowedfiletypes'),
        'requestwindowtype' => 'days-after-close',
    ]);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$mform->display();
echo $OUTPUT->footer();
