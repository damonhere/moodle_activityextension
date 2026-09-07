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
 * Per-course extension request configuration form.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * The two quizextensionmanager_crsset fields: maxrequestsperstudent,
 * defaultrequestwindowdays.
 *
 * Expected $customdata keys: 'courseid' (int).
 */
class course_settings_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement(
            'text',
            'maxrequestsperstudent',
            get_string('coursesettings:maxrequestsperstudent', 'local_quizextensionmanager')
        );
        $mform->setType('maxrequestsperstudent', PARAM_RAW);
        $mform->addHelpButton('maxrequestsperstudent', 'coursesettings:maxrequestsperstudent', 'local_quizextensionmanager');

        $mform->addElement(
            'text',
            'defaultrequestwindowdays',
            get_string('coursesettings:defaultrequestwindowdays', 'local_quizextensionmanager')
        );
        $mform->setType('defaultrequestwindowdays', PARAM_RAW);
        $mform->addHelpButton('defaultrequestwindowdays', 'coursesettings:defaultrequestwindowdays', 'local_quizextensionmanager');

        $this->add_action_buttons();
    }

    /**
     * Validate that the numeric fields, if supplied, are non-negative integers.
     *
     * @param array $data submitted form data.
     * @param array $files submitted files.
     * @return array errors, keyed by element name.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $max = trim((string) ($data['maxrequestsperstudent'] ?? ''));
        if ($max !== '' && (!is_numeric($max) || (int) $max < 0)) {
            $errors['maxrequestsperstudent'] = get_string('error:invalidnumber', 'local_quizextensionmanager');
        }

        $days = trim((string) ($data['defaultrequestwindowdays'] ?? ''));
        if ($days !== '' && (!is_numeric($days) || (int) $days < 0)) {
            $errors['defaultrequestwindowdays'] = get_string('error:invalidnumber', 'local_quizextensionmanager');
        }

        return $errors;
    }
}
