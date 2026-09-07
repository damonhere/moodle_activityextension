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
 * Deny-request modal form (AJAX, via core_form\dynamic_form).
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
 * Shown in a modal from the pending-requests dashboard. Takes only an
 * optional reviewer comment and submits via AJAX -- the dashboard removes
 * the row on success without a page reload. See PLUGIN_SPEC.md v0.4.
 *
 * Takes a single arg: 'requestid' (int).
 */
class deny_form extends dynamic_form {

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
     * Check the current user may deny this specific request.
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('local/quizextensionmanager:manage', $this->get_request_context());

        if ($this->get_request()->status !== 'pending') {
            throw new \moodle_exception('error:notpending', 'local_quizextensionmanager');
        }
    }

    /**
     * Nothing to prefill beyond the hidden requestid.
     */
    public function set_data_for_dynamic_submission(): void {
        $this->set_data((object) ['requestid' => $this->get_request()->id]);
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
     * Process the form submission: deny the request.
     *
     * @return array ['requestid' => int, 'status' => string]
     */
    public function process_dynamic_submission() {
        global $USER;

        $data = $this->get_data();
        $record = request_manager::deny_request($data->requestid, $USER->id, $data->reviewreason ?? '');

        return [
            'requestid' => (int) $record->id,
            'status' => $record->status,
        ];
    }
}
