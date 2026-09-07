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
 * Student-facing extension request form.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Shows the quiz's current close date/time limit/attempts as read-only
 * static info, then editable requested new values plus an optional/required
 * reason and documentation upload.
 *
 * Expected $customdata keys: 'cmid' (int), 'quiz' (stdClass quiz record),
 * 'quizset' (stdClass|false quizextensionmanager_quizset record),
 * 'requestid' (int, 0 for a new request), 'filemanageroptions' (array).
 */
class request_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;
        $quiz = $this->_customdata['quiz'];
        $quizset = $this->_customdata['quizset'] ?? null;
        $requestid = $this->_customdata['requestid'] ?? 0;

        $mform->addElement('hidden', 'cmid', $this->_customdata['cmid']);
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'id', $requestid);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('header', 'currentvalues', get_string('form:currentvalues', 'local_quizextensionmanager'));

        $mform->addElement(
            'static',
            'currenttimeclosedisplay',
            get_string('form:currenttimeclose', 'local_quizextensionmanager'),
            !empty($quiz->timeclose) ? userdate($quiz->timeclose) : get_string('noclosedate', 'local_quizextensionmanager')
        );
        $mform->addElement(
            'static',
            'currenttimelimitdisplay',
            get_string('form:currenttimelimit', 'local_quizextensionmanager'),
            !empty($quiz->timelimit) ? format_time($quiz->timelimit) : get_string('nolimit', 'local_quizextensionmanager')
        );
        $mform->addElement(
            'static',
            'currentattemptsdisplay',
            get_string('form:currentattempts', 'local_quizextensionmanager'),
            !empty($quiz->attempts) ? $quiz->attempts : get_string('unlimited', 'local_quizextensionmanager')
        );

        $mform->addElement('header', 'requestedvalues', get_string('form:requestedvalues', 'local_quizextensionmanager'));

        $mform->addElement(
            'date_time_selector',
            'requestedtimeclose',
            get_string('form:requestedtimeclose', 'local_quizextensionmanager'),
            ['optional' => true]
        );

        $mform->addElement(
            'duration',
            'requestedtimelimit',
            get_string('form:requestedtimelimit', 'local_quizextensionmanager'),
            ['optional' => true]
        );

        $mform->addElement('text', 'requestedattempts', get_string('form:requestedattempts', 'local_quizextensionmanager'));
        $mform->setType('requestedattempts', PARAM_RAW);
        $mform->addHelpButton('requestedattempts', 'form:requestedattempts', 'local_quizextensionmanager');

        $requirereason = $quizset->requirereason ?? get_config('local_quizextensionmanager', 'reasonrequired');
        $mform->addElement(
            'textarea',
            'reason',
            get_string('form:reason', 'local_quizextensionmanager'),
            ['rows' => 4, 'cols' => 60]
        );
        $mform->setType('reason', PARAM_TEXT);
        if (!empty($requirereason)) {
            $mform->addRule('reason', get_string('required'), 'required', null, 'client');
        }

        $documentationmode = $quizset->documentationmode ?? get_config('local_quizextensionmanager', 'documentationmode');
        if ($documentationmode !== 'none') {
            $manageroptions = $this->_customdata['filemanageroptions'] ?? [
                'subdirs' => 0,
                'maxfiles' => 5,
                'accepted_types' => '*',
            ];

            $mform->addElement(
                'filemanager',
                'documentation_filemanager',
                get_string('form:documentation', 'local_quizextensionmanager'),
                null,
                $manageroptions
            );

            if ($documentationmode === 'required') {
                $mform->addRule('documentation_filemanager', get_string('required'), 'required', null, 'client');
            }
        }

        $this->add_action_buttons(true, get_string('form:submit', 'local_quizextensionmanager'));
    }

    /**
     * Validate that at least one requested field actually differs from the
     * quiz's current value, and enforce requirereason.
     *
     * @param array $data submitted form data.
     * @param array $files submitted files.
     * @return array errors, keyed by element name.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $quiz = $this->_customdata['quiz'];
        $quizset = $this->_customdata['quizset'] ?? null;

        $samecl = empty($data['requestedtimeclose']) || (int) $data['requestedtimeclose'] === (int) $quiz->timeclose;
        $sametl = empty($data['requestedtimelimit']) || (int) $data['requestedtimelimit'] === (int) $quiz->timelimit;

        $requestedattempts = trim((string) ($data['requestedattempts'] ?? ''));
        $sameatt = ($requestedattempts === '') || ((int) $requestedattempts === (int) $quiz->attempts);

        if ($requestedattempts !== '' && !is_numeric($requestedattempts)) {
            $errors['requestedattempts'] = get_string('error:invalidnumber', 'local_quizextensionmanager');
        } else if ($samecl && $sametl && $sameatt) {
            $errors['requestedattempts'] = get_string('error:nochange', 'local_quizextensionmanager');
        }

        $requirereason = $quizset->requirereason ?? get_config('local_quizextensionmanager', 'reasonrequired');
        if (!empty($requirereason) && trim((string) ($data['reason'] ?? '')) === '') {
            $errors['reason'] = get_string('required');
        }

        return $errors;
    }
}
