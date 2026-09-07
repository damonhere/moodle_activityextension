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
 * Bulk course-wide extension request report page.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');

use local_quizextensionmanager\table\requests_table;

$courseid = required_param('courseid', PARAM_INT);
$status = optional_param('status', '', PARAM_ALPHA);

$course = get_course($courseid);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/quizextensionmanager:manage', $context);

$PAGE->set_url('/local/quizextensionmanager/report.php', array_filter(['courseid' => $courseid, 'status' => $status]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$title = get_string('page:report', 'local_quizextensionmanager');
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$statuses = [
    '' => get_string('status:all', 'local_quizextensionmanager'),
    'pending' => get_string('status:pending', 'local_quizextensionmanager'),
    'approved' => get_string('status:approved', 'local_quizextensionmanager'),
    'denied' => get_string('status:denied', 'local_quizextensionmanager'),
    'cancelled' => get_string('status:cancelled', 'local_quizextensionmanager'),
];

$select = new single_select(
    new moodle_url('/local/quizextensionmanager/report.php', ['courseid' => $courseid]),
    'status',
    $statuses,
    $status,
    null
);
$select->label = get_string('table:status', 'local_quizextensionmanager');
echo $OUTPUT->render($select);

$table = new requests_table('local-quizextensionmanager-report', $courseid, $status);
$table->define_baseurl($PAGE->url);
$table->out(25, true);

echo $OUTPUT->footer();
