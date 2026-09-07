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
 * Renders the "Request extension" entry point on the quiz view page.
 *
 * This is the single integration point that quizaccess_quizextensionmanager
 * calls from its access rule description() hook, so that all of the actual
 * business logic and markup live here in local_quizextensionmanager.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper for rendering the quiz view page "Request extension" link.
 */
class extension_link {

    /**
     * Build the HTML for the "Request extension" link/button shown on
     * mod/quiz/view.php, or an empty string if the current user should not
     * see one (e.g. no capability, or nothing useful to show).
     *
     * Logic:
     *  - Requires local/quizextensionmanager:request in the quiz's module context.
     *  - If the user already has a pending request for this quiz, shows a
     *    link to myrequests.php instead of a new-request link (a pending
     *    request blocks submitting another one).
     *  - Otherwise, if eligibility::can_request() allows it, shows a link to
     *    request.php to start a new request.
     *  - If the user has any past requests (approved/denied/cancelled) for
     *    this quiz, also shows a link to view them.
     *  - Returns '' if there is nothing useful to show (e.g. the window has
     *    closed and there is no prior request to view).
     *
     * @param int $quizid the quiz instance id.
     * @param int $courseid the course id the quiz belongs to.
     * @param int $userid the user viewing the quiz (0 = current user).
     * @return string HTML to render, or an empty string to render nothing.
     */
    public static function render(int $quizid, int $courseid, int $userid = 0): string {
        global $DB, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $cm = get_coursemodule_from_instance('quiz', $quizid, $courseid, false, IGNORE_MISSING);
        if (!$cm) {
            return '';
        }

        $context = \context_module::instance($cm->id);
        if (!has_capability('local/quizextensionmanager:request', $context, $userid)) {
            return '';
        }

        $myrequestsurl = new \moodle_url('/local/quizextensionmanager/myrequests.php', ['cmid' => $cm->id]);

        $haspending = $DB->record_exists('quizextensionmanager_request', [
            'quizid' => $quizid,
            'userid' => $userid,
            'status' => 'pending',
        ]);

        if ($haspending) {
            return \html_writer::div(
                \html_writer::link($myrequestsurl, get_string('link:pendingrequest', 'local_quizextensionmanager')),
                'local-quizextensionmanager-link'
            );
        }

        $hasany = $DB->record_exists('quizextensionmanager_request', ['quizid' => $quizid, 'userid' => $userid]);

        $eligibility = \local_quizextensionmanager\eligibility::can_request($quizid, $userid);

        $parts = [];
        if ($eligibility->allowed) {
            $requesturl = new \moodle_url('/local/quizextensionmanager/request.php', ['cmid' => $cm->id]);
            $parts[] = \html_writer::link($requesturl, get_string('link:requestextension', 'local_quizextensionmanager'));
        }
        if ($hasany) {
            $parts[] = \html_writer::link($myrequestsurl, get_string('link:viewrequests', 'local_quizextensionmanager'));
        }

        if (empty($parts)) {
            return '';
        }

        $html = implode(' ', $parts);
        if (!$eligibility->allowed) {
            $reason = $eligibility->get_reason_string();
            if ($reason !== '') {
                $html = \html_writer::span($html, '', ['title' => $reason]);
            }
        }

        return \html_writer::div($html, 'local-quizextensionmanager-link');
    }
}
