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
 * Sends approved/denied extension request notifications via Moodle's
 * message API.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds and sends the approved/denied outcome message to the requesting
 * student, using the admin-configured subject/body templates when set, or a
 * sensible default lang-string template otherwise.
 *
 * Template placeholder contract: admin-configured templates support simple
 * "{$a->key}" style substitution (via plain str_replace, not get_string()),
 * with the following keys available: quizname, coursename,
 * requestedtimeclose, grantedtimeclose, requestedtimelimit, grantedtimelimit,
 * requestedattempts, grantedattempts, reviewreason.
 */
class notification_manager {

    /**
     * Send the "request approved" notification for a request.
     *
     * @param \stdClass $request the approved request record.
     */
    public static function send_approved(\stdClass $request): void {
        self::send($request, 'approved');
    }

    /**
     * Send the "request denied" notification for a request.
     *
     * @param \stdClass $request the denied request record.
     */
    public static function send_denied(\stdClass $request): void {
        self::send($request, 'denied');
    }

    /**
     * Build and send the outcome notification for a request.
     *
     * @param \stdClass $request the request record.
     * @param string $outcome 'approved' or 'denied'.
     */
    protected static function send(\stdClass $request, string $outcome): void {
        global $DB;

        $student = \core_user::get_user($request->userid);
        if (!$student || isguestuser($student)) {
            return;
        }

        $quiz = $DB->get_record('quiz', ['id' => $request->quizid]);
        $course = $DB->get_record('course', ['id' => $request->courseid]);

        $a = new \stdClass();
        $a->quizname = $quiz ? format_string($quiz->name) : '';
        $a->coursename = $course ? format_string($course->fullname) : '';
        $a->requestedtimeclose = self::format_date($request->requestedtimeclose);
        $a->grantedtimeclose = self::format_date($request->grantedtimeclose);
        $a->requestedtimelimit = self::format_duration($request->requestedtimelimit);
        $a->grantedtimelimit = self::format_duration($request->grantedtimelimit);
        $a->requestedattempts = ($request->requestedattempts !== null && $request->requestedattempts !== '')
            ? $request->requestedattempts : get_string('unchanged', 'local_quizextensionmanager');
        $a->grantedattempts = ($request->grantedattempts !== null && $request->grantedattempts !== '')
            ? $request->grantedattempts : get_string('unchanged', 'local_quizextensionmanager');
        $a->reviewreason = (string) ($request->reviewreason ?? '');

        if ($outcome === 'approved') {
            $subjecttpl = get_config('local_quizextensionmanager', 'notifyapprovedsubject');
            $bodytpl = get_config('local_quizextensionmanager', 'notifyapprovedbody');
            $defaultsubjectkey = 'notify:approvedsubject:default';
            $defaultbodykey = 'notify:approvedbody:default';
            $messagename = 'extensionapproved';
        } else {
            $subjecttpl = get_config('local_quizextensionmanager', 'notifydeniedsubject');
            $bodytpl = get_config('local_quizextensionmanager', 'notifydeniedbody');
            $defaultsubjectkey = 'notify:deniedsubject:default';
            $defaultbodykey = 'notify:deniedbody:default';
            $messagename = 'extensiondenied';
        }

        $subject = (trim((string) $subjecttpl) !== '')
            ? self::apply_placeholders($subjecttpl, $a)
            : get_string($defaultsubjectkey, 'local_quizextensionmanager', $a);

        $body = (trim((string) $bodytpl) !== '')
            ? self::apply_placeholders($bodytpl, $a)
            : get_string($defaultbodykey, 'local_quizextensionmanager', $a);

        $message = new \core\message\message();
        $message->component = 'local_quizextensionmanager';
        $message->name = $messagename;
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $student;
        $message->subject = $subject;
        $message->fullmessage = $body;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = nl2br(s($body));
        $message->smallmessage = $subject;
        $message->notification = 1;

        $cm = get_coursemodule_from_instance('quiz', $request->quizid, 0, false, IGNORE_MISSING);
        if ($cm) {
            $message->contexturl = (new \moodle_url(
                '/local/quizextensionmanager/myrequests.php',
                ['cmid' => $cm->id]
            ))->out(false);
            $message->contexturlname = get_string('pluginname', 'local_quizextensionmanager');
        }

        message_send($message);
    }

    /**
     * Format a timestamp for display, or a placeholder if unset.
     *
     * @param int|null $timestamp
     * @return string
     */
    protected static function format_date($timestamp): string {
        return !empty($timestamp) ? userdate($timestamp) : get_string('unchanged', 'local_quizextensionmanager');
    }

    /**
     * Format a duration in seconds for display, or a placeholder if unset.
     *
     * @param int|null $seconds
     * @return string
     */
    protected static function format_duration($seconds): string {
        return !empty($seconds) ? format_time($seconds) : get_string('unchanged', 'local_quizextensionmanager');
    }

    /**
     * Apply "{$a->placeholder}" style substitution to an admin-configured
     * template. Uses a plain str_replace pass rather than get_string(),
     * since these templates are site config, not language-pack strings.
     *
     * @param string $template the template text.
     * @param \stdClass $a the placeholder values, keyed by placeholder name.
     * @return string
     */
    protected static function apply_placeholders(string $template, \stdClass $a): string {
        $search = [];
        $replace = [];
        foreach (get_object_vars($a) as $key => $value) {
            $search[] = '{$a->' . $key . '}';
            $replace[] = (string) $value;
        }
        return str_replace($search, $replace, $template);
    }
}
