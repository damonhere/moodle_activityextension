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
 * Eligibility checks for whether a student may submit a new extension request.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager;

defined('MOODLE_INTERNAL') || die();

/**
 * Determines whether a given user may currently submit a new extension
 * request for a given quiz, checking (in order): per-quiz enabled flag, quiz
 * open state, the request window, an existing pending duplicate, and the
 * course quota.
 */
class eligibility {

    /**
     * Can the given user submit a new extension request for the given quiz right now?
     *
     * Checks, in order:
     *  1. Per-quiz quizextensionmanager_quizset.enabled (default true if no settings row exists).
     *  2. The quiz is already open (timeopen = 0 means always open).
     *  3. The request window (per quizextensionmanager_quizset.requestwindowtype) has not closed.
     *  4. No existing PENDING request already open for this quiz+user (a prior
     *     approved/denied/cancelled request does not block a new one).
     *  5. The course's maxrequestsperstudent quota, counted as approved-status
     *     requests only, has not been reached.
     *
     * @param int $quizid the quiz instance id.
     * @param int $userid the user id to check.
     * @return eligibility_result
     */
    public static function can_request(int $quizid, int $userid): eligibility_result {
        global $DB;

        $quiz = $DB->get_record('quiz', ['id' => $quizid]);
        if (!$quiz) {
            return new eligibility_result(false, 'eligibility:noquiz');
        }

        $quizset = $DB->get_record('quizextensionmanager_quizset', ['quizid' => $quizid]);

        // 1. Per-quiz enabled flag. Defaults to enabled if no settings row exists yet.
        if ($quizset && !$quizset->enabled) {
            return new eligibility_result(false, 'eligibility:disabled');
        }

        $now = time();

        // 2. Quiz open state. timeopen = 0 means the quiz is always open.
        if (!empty($quiz->timeopen) && $quiz->timeopen > $now) {
            return new eligibility_result(false, 'eligibility:notopenyet');
        }

        // 3. Request window.
        $windowend = self::get_request_window_end($quiz, $quizset);
        if ($windowend !== null && $now > $windowend) {
            return new eligibility_result(false, 'eligibility:windowclosed');
        }

        // 4. Duplicate pending request.
        $pending = $DB->record_exists('quizextensionmanager_request', [
            'quizid' => $quizid,
            'userid' => $userid,
            'status' => 'pending',
        ]);
        if ($pending) {
            return new eligibility_result(false, 'eligibility:pendingexists');
        }

        // 5. Course quota, approved-status requests only.
        [$used, $max] = request_manager::get_quota($quiz->course, $userid);
        if ($max !== null && $used >= $max) {
            return new eligibility_result(false, 'eligibility:quotaexceeded');
        }

        return new eligibility_result(true);
    }

    /**
     * Compute the timestamp at which the request window closes for a quiz, or
     * null if the window is open-ended/unlimited.
     *
     * @param \stdClass $quiz the quiz record.
     * @param \stdClass|false $quizset the quizextensionmanager_quizset record, or false if none exists.
     * @return int|null the window close timestamp, or null if unlimited.
     */
    protected static function get_request_window_end(\stdClass $quiz, $quizset): ?int {
        global $DB;

        $type = ($quizset && !empty($quizset->requestwindowtype)) ? $quizset->requestwindowtype : 'days-after-close';

        switch ($type) {
            case 'fixed-date':
                // Nullable timestamp. Defensively treat an unset fixed date as unlimited
                // rather than immediately closing every request window.
                return ($quizset && !empty($quizset->requestwindowdate)) ? (int) $quizset->requestwindowdate : null;

            case 'until-course-end':
                $course = $DB->get_record('course', ['id' => $quiz->course], 'id, enddate');
                // A course enddate of 0 means "no course end" -- unlimited window.
                return ($course && !empty($course->enddate)) ? (int) $course->enddate : null;

            case 'days-after-close':
            default:
                // If the quiz never closes, the days-after-close window is open-ended.
                if (empty($quiz->timeclose)) {
                    return null;
                }

                $days = ($quizset && $quizset->requestwindowdays !== null && $quizset->requestwindowdays !== '')
                    ? (int) $quizset->requestwindowdays
                    : null;

                if ($days === null) {
                    // Fall back to the course default when the quiz has no override.
                    $crsset = $DB->get_record('quizextensionmanager_crsset', ['courseid' => $quiz->course]);
                    if ($crsset && $crsset->defaultrequestwindowdays !== null && $crsset->defaultrequestwindowdays !== '') {
                        $days = (int) $crsset->defaultrequestwindowdays;
                    }
                }

                if ($days === null) {
                    // No window configured anywhere: treat as unlimited rather than
                    // silently blocking every request.
                    return null;
                }

                return (int) $quiz->timeclose + ($days * DAYSECS);
        }
    }
}
