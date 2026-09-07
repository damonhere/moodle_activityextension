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
 * CRUD and state-transition logic for quizextensionmanager_request rows.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager;

defined('MOODLE_INTERNAL') || die();

/**
 * Creates, edits, cancels, approves and denies extension requests, and
 * provides read helpers used by the student/teacher-facing pages.
 */
class request_manager {

    /** @var string The main request table name. */
    const TABLE = 'quizextensionmanager_request';

    /**
     * Get a single request by id.
     *
     * @param int $id request id.
     * @return \stdClass
     */
    public static function get_request(int $id): \stdClass {
        global $DB;
        return $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Get a user's requests, optionally filtered by quiz, newest first.
     *
     * @param int $userid the student's user id.
     * @param int|null $quizid optionally restrict to a single quiz.
     * @return \stdClass[]
     */
    public static function get_user_requests(int $userid, ?int $quizid = null): array {
        global $DB;

        $params = ['userid' => $userid];
        $select = 'userid = :userid';
        if ($quizid !== null) {
            $select .= ' AND quizid = :quizid';
            $params['quizid'] = $quizid;
        }

        return $DB->get_records_select(self::TABLE, $select, $params, 'timecreated DESC');
    }

    /**
     * Get pending requests for a quiz, oldest first (for a teacher dashboard queue).
     *
     * @param int $quizid the quiz instance id.
     * @return \stdClass[]
     */
    public static function get_pending_requests(int $quizid): array {
        global $DB;
        return $DB->get_records(self::TABLE, ['quizid' => $quizid, 'status' => 'pending'], 'timecreated ASC');
    }

    /**
     * Get all requests for a course, optionally filtered by status, newest first.
     *
     * @param int $courseid the course id.
     * @param string|null $status optional status filter (pending/approved/denied/cancelled).
     * @return \stdClass[]
     */
    public static function get_course_requests(int $courseid, ?string $status = null): array {
        global $DB;

        $params = ['courseid' => $courseid];
        $select = 'courseid = :courseid';
        if (!empty($status)) {
            $select .= ' AND status = :status';
            $params['status'] = $status;
        }

        return $DB->get_records_select(self::TABLE, $select, $params, 'timecreated DESC');
    }

    /**
     * Get a student's quota usage for a course.
     *
     * Per the spec's Resolved Decisions, "used" counts approved requests
     * only -- denied and cancelled requests do not count against the quota.
     *
     * @param int $courseid the course id.
     * @param int $userid the student's user id.
     * @return array [int $used, int|null $max] where $max is null for unlimited.
     */
    public static function get_quota(int $courseid, int $userid): array {
        global $DB;

        $used = $DB->count_records(self::TABLE, [
            'courseid' => $courseid,
            'userid' => $userid,
            'status' => 'approved',
        ]);

        $crsset = $DB->get_record('quizextensionmanager_crsset', ['courseid' => $courseid]);
        $max = ($crsset && $crsset->maxrequestsperstudent !== null && $crsset->maxrequestsperstudent !== '')
            ? (int) $crsset->maxrequestsperstudent
            : null;

        return [$used, $max];
    }

    /**
     * Create a new pending extension request, after re-checking eligibility.
     *
     * Snapshots the quiz's current timeclose/timelimit/attempts onto the
     * request row so the UI/audit trail still shows what the student was
     * comparing against even if the quiz's own settings change later.
     *
     * @param int $quizid the quiz instance id.
     * @param int $userid the requesting student's user id.
     * @param array $data ['requestedtimeclose' => ?int, 'requestedtimelimit' => ?int,
     *      'requestedattempts' => ?int, 'reason' => string].
     * @return \stdClass the newly created request record.
     * @throws \moodle_exception if the user is not currently eligible to request.
     */
    public static function create_request(int $quizid, int $userid, array $data): \stdClass {
        global $DB;

        $eligibility = eligibility::can_request($quizid, $userid);
        if (!$eligibility->allowed) {
            throw new \moodle_exception($eligibility->reasoncode, 'local_quizextensionmanager', '', $eligibility->reasonargs);
        }

        $quiz = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);

        $record = new \stdClass();
        $record->quizid = $quizid;
        $record->courseid = $quiz->course;
        $record->userid = $userid;
        $record->requestedtimeclose = $data['requestedtimeclose'] ?? null;
        $record->requestedtimelimit = $data['requestedtimelimit'] ?? null;
        $record->requestedattempts = $data['requestedattempts'] ?? null;
        $record->currenttimeclose = $quiz->timeclose;
        $record->currenttimelimit = $quiz->timelimit;
        $record->currentattempts = $quiz->attempts;
        $record->reason = $data['reason'] ?? '';
        $record->status = 'pending';
        $record->grantedtimeclose = null;
        $record->grantedtimelimit = null;
        $record->grantedattempts = null;
        $record->quizoverrideid = null;
        $record->reviewerid = null;
        $record->reviewreason = null;

        $now = time();
        $record->timecreated = $now;
        $record->timereviewed = null;
        $record->timemodified = $now;

        $record->id = $DB->insert_record(self::TABLE, $record);

        return $record;
    }

    /**
     * Update a request's requested fields/reason. Only allowed while pending
     * and only by the original requester.
     *
     * @param int $requestid the request id.
     * @param int $userid the user attempting the edit (must be the original requester).
     * @param array $data any of ['requestedtimeclose', 'requestedtimelimit', 'requestedattempts', 'reason'].
     * @return \stdClass the updated request record.
     * @throws \moodle_exception if the caller is not the owner or the request is not pending.
     */
    public static function update_request(int $requestid, int $userid, array $data): \stdClass {
        global $DB;

        $record = self::get_request($requestid);

        if ((int) $record->userid !== (int) $userid) {
            throw new \moodle_exception('error:notowner', 'local_quizextensionmanager');
        }
        if ($record->status !== 'pending') {
            throw new \moodle_exception('error:notpending', 'local_quizextensionmanager');
        }

        foreach (['requestedtimeclose', 'requestedtimelimit', 'requestedattempts', 'reason'] as $field) {
            if (array_key_exists($field, $data)) {
                $record->$field = $data[$field];
            }
        }
        $record->timemodified = time();

        $DB->update_record(self::TABLE, $record);

        return $record;
    }

    /**
     * Cancel a pending request. Only allowed while pending and only by the
     * original requester. Sets status=cancelled rather than deleting the row.
     *
     * @param int $requestid the request id.
     * @param int $userid the user attempting the cancellation (must be the original requester).
     * @throws \moodle_exception if the caller is not the owner or the request is not pending.
     */
    public static function cancel_request(int $requestid, int $userid): void {
        global $DB;

        $record = self::get_request($requestid);

        if ((int) $record->userid !== (int) $userid) {
            throw new \moodle_exception('error:notowner', 'local_quizextensionmanager');
        }
        if ($record->status !== 'pending') {
            throw new \moodle_exception('error:notpending', 'local_quizextensionmanager');
        }

        $record->status = 'cancelled';
        $record->timemodified = time();
        $DB->update_record(self::TABLE, $record);
    }

    /**
     * Approve a pending request.
     *
     * Each granted* field defaults to the corresponding requested* value
     * unless the reviewer explicitly supplied an override in $granted.
     * requested* may be null ("no change requested" for that field), in
     * which case granted* also stays null/unchanged. After updating the
     * request row, applies the change to a mod_quiz user override via
     * override_manager and stores the resulting override id back onto
     * quizoverrideid, then sends the approved notification.
     *
     * @param int $requestid the request id.
     * @param int $reviewerid the reviewing teacher's user id.
     * @param array $granted optional overrides: any of ['grantedtimeclose', 'grantedtimelimit', 'grantedattempts'].
     * @param string $reviewreason optional comment left by the reviewer.
     * @return \stdClass the updated (approved) request record.
     * @throws \moodle_exception if the request is not pending.
     */
    public static function approve_request(int $requestid, int $reviewerid, array $granted = [], string $reviewreason = ''): \stdClass {
        global $DB;

        $record = self::get_request($requestid);

        if ($record->status !== 'pending') {
            throw new \moodle_exception('error:notpending', 'local_quizextensionmanager');
        }

        $record->grantedtimeclose = array_key_exists('grantedtimeclose', $granted)
            ? $granted['grantedtimeclose'] : $record->requestedtimeclose;
        $record->grantedtimelimit = array_key_exists('grantedtimelimit', $granted)
            ? $granted['grantedtimelimit'] : $record->requestedtimelimit;
        $record->grantedattempts = array_key_exists('grantedattempts', $granted)
            ? $granted['grantedattempts'] : $record->requestedattempts;

        $record->status = 'approved';
        $record->reviewerid = $reviewerid;
        $record->reviewreason = $reviewreason;

        $now = time();
        $record->timereviewed = $now;
        $record->timemodified = $now;

        $DB->update_record(self::TABLE, $record);

        $overrideid = override_manager::apply_override($record);
        if ($overrideid) {
            $record->quizoverrideid = $overrideid;
            $DB->set_field(self::TABLE, 'quizoverrideid', $overrideid, ['id' => $record->id]);
        }

        notification_manager::send_approved($record);

        return $record;
    }

    /**
     * Deny a pending request.
     *
     * @param int $requestid the request id.
     * @param int $reviewerid the reviewing teacher's user id.
     * @param string $reviewreason optional comment left by the reviewer.
     * @return \stdClass the updated (denied) request record.
     * @throws \moodle_exception if the request is not pending.
     */
    public static function deny_request(int $requestid, int $reviewerid, string $reviewreason = ''): \stdClass {
        global $DB;

        $record = self::get_request($requestid);

        if ($record->status !== 'pending') {
            throw new \moodle_exception('error:notpending', 'local_quizextensionmanager');
        }

        $record->status = 'denied';
        $record->reviewerid = $reviewerid;
        $record->reviewreason = $reviewreason;

        $now = time();
        $record->timereviewed = $now;
        $record->timemodified = $now;

        $DB->update_record(self::TABLE, $record);

        notification_manager::send_denied($record);

        return $record;
    }
}
