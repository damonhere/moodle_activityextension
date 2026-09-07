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
 * Approve-request modal form (AJAX, via core_form\dynamic_form).
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager\form;

use context;
use context_module;
use core_form\dynamic_form;
use moodle_url;
use local_quizextensionmanager\request_manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Shown in a modal from the pending-requests dashboard. Grants the request's
 * requested values by default (editable), takes an optional reviewer
 * comment, and submits via AJAX -- the dashboard removes the row on success
 * without a page reload. See PLUGIN_SPEC.md v0.4.
 *
 * Takes a single arg: 'requestid' (int).
 */
class approve_form extends dynamic_form {

    /**
     * Look up the pending request this form instance is acting on.
     *
     * @return \stdClass
     */
    protected function get_request(): \stdClass {
        $requestid = $this->optional_param('requestid', 0, PARAM_INT);
        return request_manager::get_request($requestid);
    }

    /**
     * Look up the quiz this request belongs to.
     *
     * @return \stdClass
     */
    protected function get_quiz(): \stdClass {
        global $DB;
        return $DB->get_record('quiz', ['id' => $this->get_request()->quizid], '*', MUST_EXIST);
    }

    /**
     * Resolve the quiz module context for this request's quiz.
     *
     * @return context_module
     */
    protected function get_request_context(): context_module {
        $request = $this->get_request();
        $cm = get_coursemodule_from_instance('quiz', $request->quizid, 0, false, MUST_EXIST);
        return context_module::instance($cm->id);
    }

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'requestid', $this->optional_param('requestid', 0, PARAM_INT));
        $mform->setType('requestid', PARAM_INT);

        $request = $this->get_request();
        $student = \core_user::get_user($request->userid);

        $mform->addElement('static', 'studentname', get_string('form:student', 'local_quizextensionmanager'), fullname($student));
        $mform->addElement(
            'static',
            'requestedreason',
            get_string('form:reason', 'local_quizextensionmanager'),
            format_text((string) $request->reason, FORMAT_PLAIN)
        );

        $documentationhtml = request_manager::get_documentation_html($request->id, $this->get_request_context());
        if ($documentationhtml !== '') {
            $mform->addElement(
                'static',
                'documentationdisplay',
                get_string('form:documentation', 'local_quizextensionmanager'),
                $documentationhtml
            );
        }

        $mform->addElement(
            'date_time_selector',
            'grantedtimeclose',
            get_string('form:grantedtimeclose', 'local_quizextensionmanager'),
            ['optional' => true]
        );
        $mform->addElement(
            'duration',
            'grantedtimelimit',
            get_string('form:grantedtimelimit', 'local_quizextensionmanager'),
            ['optional' => true]
        );
        // Shown so the teacher can compare the current total against the
        // granted total below and see the actual difference, rather than
        // just a bare number with nothing to compare it to.
        $mform->addElement(
            'static',
            'currentattemptsdisplay',
            get_string('form:currentattempts', 'local_quizextensionmanager'),
            !empty($this->get_quiz()->attempts) ? $this->get_quiz()->attempts : get_string('unlimited', 'local_quizextensionmanager')
        );
        $mform->addElement('text', 'grantedattempts', get_string('form:grantedattempts', 'local_quizextensionmanager'));
        $mform->setType('grantedattempts', PARAM_RAW);

        $mform->addElement(
            'textarea',
            'reviewreason',
            get_string('form:reviewreason', 'local_quizextensionmanager'),
            ['rows' => 3, 'cols' => 60]
        );
        $mform->setType('reviewreason', PARAM_TEXT);
    }

    /**
     * Return form context.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return $this->get_request_context();
    }

    /**
     * Check the current user may approve this specific request.
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('local/quizextensionmanager:manage', $this->get_request_context());

        if ($this->get_request()->status !== 'pending') {
            throw new \moodle_exception('error:notpending', 'local_quizextensionmanager');
        }
    }

    /**
     * Load in the request's requested values as the granted defaults.
     *
     * When the student didn't request a change to the time limit, default
     * to the quiz's own current time limit rather than a bare 0, so it
     * doesn't read as "grant a 0-minute limit".
     */
    public function set_data_for_dynamic_submission(): void {
        $request = $this->get_request();

        $this->set_data((object) [
            'requestid' => $request->id,
            'grantedtimeclose' => !empty($request->requestedtimeclose) ? $request->requestedtimeclose : 0,
            'grantedtimelimit' => !empty($request->requestedtimelimit) ? $request->requestedtimelimit : $this->get_quiz()->timelimit,
            'grantedattempts' => ($request->requestedattempts !== null) ? $request->requestedattempts : '',
        ]);
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $request = $this->get_request();
        $cm = get_coursemodule_from_instance('quiz', $request->quizid, 0, false, MUST_EXIST);
        return new moodle_url('/local/quizextensionmanager/manage.php', ['cmid' => $cm->id]);
    }

    /**
     * Process the form submission: approve the request.
     *
     * @return array ['requestid' => int, 'status' => string]
     */
    public function process_dynamic_submission() {
        global $USER;

        $data = $this->get_data();

        $grantedattempts = trim((string) ($data->grantedattempts ?? ''));
        $granted = [
            'grantedtimeclose' => !empty($data->grantedtimeclose) ? (int) $data->grantedtimeclose : null,
            'grantedtimelimit' => !empty($data->grantedtimelimit) ? (int) $data->grantedtimelimit : null,
            'grantedattempts' => ($grantedattempts !== '') ? (int) $grantedattempts : null,
        ];

        $record = request_manager::approve_request($data->requestid, $USER->id, $granted, $data->reviewreason ?? '');

        return [
            'requestid' => (int) $record->id,
            'status' => $record->status,
        ];
    }
}
