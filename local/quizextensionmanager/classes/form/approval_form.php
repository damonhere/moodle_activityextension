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
 * Teacher-facing approve/deny form for a single extension request.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Shows the request's details, prefilled granted close/timelimit/attempts
 * (defaulting to the requested values), a reviewreason textarea, and two
 * submit buttons (Approve / Deny) so a single submission can branch on which
 * was pressed.
 *
 * Expected $customdata keys: 'request' (stdClass request record), 'cmid' (int).
 */
class approval_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;
        $request = $this->_customdata['request'];

        $mform->addElement('hidden', 'requestid', $request->id);
        $mform->setType('requestid', PARAM_INT);

        $mform->addElement('hidden', 'cmid', $this->_customdata['cmid']);
        $mform->setType('cmid', PARAM_INT);

        $student = \core_user::get_user($request->userid);

        $mform->addElement('static', 'studentname', get_string('form:student', 'local_quizextensionmanager'), fullname($student));
        $mform->addElement(
            'static',
            'currentvsrequested',
            get_string('form:currenttimeclose', 'local_quizextensionmanager'),
            (!empty($request->currenttimeclose) ? userdate($request->currenttimeclose) : get_string('noclosedate', 'local_quizextensionmanager'))
        );
        $mform->addElement(
            'static',
            'requestedreason',
            get_string('form:reason', 'local_quizextensionmanager'),
            format_text((string) $request->reason, FORMAT_PLAIN)
        );

        $mform->addElement(
            'date_time_selector',
            'grantedtimeclose',
            get_string('form:grantedtimeclose', 'local_quizextensionmanager'),
            ['optional' => true]
        );
        $mform->setDefault('grantedtimeclose', !empty($request->requestedtimeclose) ? $request->requestedtimeclose : 0);

        $mform->addElement(
            'duration',
            'grantedtimelimit',
            get_string('form:grantedtimelimit', 'local_quizextensionmanager'),
            ['optional' => true]
        );
        $mform->setDefault('grantedtimelimit', !empty($request->requestedtimelimit) ? $request->requestedtimelimit : 0);

        $mform->addElement('text', 'grantedattempts', get_string('form:grantedattempts', 'local_quizextensionmanager'));
        $mform->setType('grantedattempts', PARAM_RAW);
        $mform->setDefault(
            'grantedattempts',
            ($request->requestedattempts !== null) ? $request->requestedattempts : ''
        );

        $mform->addElement(
            'textarea',
            'reviewreason',
            get_string('form:reviewreason', 'local_quizextensionmanager'),
            ['rows' => 3, 'cols' => 60]
        );
        $mform->setType('reviewreason', PARAM_TEXT);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'approve', get_string('action:approve', 'local_quizextensionmanager'));
        $buttonarray[] = $mform->createElement('submit', 'deny', get_string('action:deny', 'local_quizextensionmanager'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }
}
