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
 * Per-course extension request settings page.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_quizextensionmanager\form\course_settings_form;

$courseid = required_param('courseid', PARAM_INT);

$course = get_course($courseid);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/quizextensionmanager:manage', $context);

$PAGE->set_url('/local/quizextensionmanager/coursesettings.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$title = get_string('page:coursesettings', 'local_quizextensionmanager');
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);

$viewurl = new moodle_url('/course/view.php', ['id' => $courseid]);

$mform = new course_settings_form(null, ['courseid' => $courseid]);

$existing = $DB->get_record('quizextensionmanager_crsset', ['courseid' => $courseid]);

if ($mform->is_cancelled()) {
    redirect($viewurl);
} else if ($data = $mform->get_data()) {
    $record = $existing ?: new stdClass();
    $record->courseid = $courseid;

    $max = trim((string) ($data->maxrequestsperstudent ?? ''));
    $record->maxrequestsperstudent = ($max !== '') ? (int) $max : null;

    $days = trim((string) ($data->defaultrequestwindowdays ?? ''));
    $record->defaultrequestwindowdays = ($days !== '') ? (int) $days : null;

    $now = time();
    $record->timemodified = $now;

    if ($existing) {
        $DB->update_record('quizextensionmanager_crsset', $record);
    } else {
        $record->timecreated = $now;
        $DB->insert_record('quizextensionmanager_crsset', $record);
    }

    redirect($viewurl, get_string('notify:settingssaved', 'local_quizextensionmanager'), null, \core\notification::SUCCESS);
} else if ($existing) {
    $mform->set_data($existing);
} else {
    $mform->set_data((object) ['courseid' => $courseid]);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$mform->display();
echo $OUTPUT->footer();
