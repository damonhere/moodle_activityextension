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
 * Per-quiz extension request configuration form.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * The six quizextensionmanager_quizset fields: enabled, requirereason,
 * documentationmode, allowedfiletypes, requestwindowtype (with its
 * days/date sub-fields shown conditionally), requestwindowdays,
 * requestwindowdate.
 *
 * Expected $customdata keys: 'cmid' (int).
 */
class quiz_settings_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'cmid', $this->_customdata['cmid']);
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('advcheckbox', 'enabled', get_string('quizsettings:enabled', 'local_quizextensionmanager'));
        $mform->setDefault('enabled', 1);

        $mform->addElement('advcheckbox', 'requirereason', get_string('quizsettings:requirereason', 'local_quizextensionmanager'));

        $mform->addElement('select', 'documentationmode', get_string('settings:documentationmode', 'local_quizextensionmanager'), [
            'none' => get_string('documentationmode:none', 'local_quizextensionmanager'),
            'optional' => get_string('documentationmode:optional', 'local_quizextensionmanager'),
            'required' => get_string('documentationmode:required', 'local_quizextensionmanager'),
        ]);

        // The standard file-type browser widget (same one used for e.g.
        // assignment submission types), rather than a bare text box.
        $mform->addElement('filetypes', 'allowedfiletypes', get_string('settings:allowedfiletypes', 'local_quizextensionmanager'));
        $mform->hideIf('allowedfiletypes', 'documentationmode', 'eq', 'none');

        $mform->addElement(
            'select',
            'requestwindowtype',
            get_string('quizsettings:requestwindowtype', 'local_quizextensionmanager'),
            [
                'days-after-close' => get_string('requestwindowtype:daysafterclose', 'local_quizextensionmanager'),
                'fixed-date' => get_string('requestwindowtype:fixeddate', 'local_quizextensionmanager'),
                'until-course-end' => get_string('requestwindowtype:untilcourseend', 'local_quizextensionmanager'),
            ]
        );

        $mform->addElement('text', 'requestwindowdays', get_string('quizsettings:requestwindowdays', 'local_quizextensionmanager'));
        $mform->setType('requestwindowdays', PARAM_RAW);
        $mform->addHelpButton('requestwindowdays', 'quizsettings:requestwindowdays', 'local_quizextensionmanager');
        $mform->hideIf('requestwindowdays', 'requestwindowtype', 'neq', 'days-after-close');

        $mform->addElement(
            'date_selector',
            'requestwindowdate',
            get_string('quizsettings:requestwindowdate', 'local_quizextensionmanager'),
            ['optional' => true]
        );
        $mform->hideIf('requestwindowdate', 'requestwindowtype', 'neq', 'fixed-date');

        $this->add_action_buttons();
    }
}
