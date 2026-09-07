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
 * Tests for \local_quizextensionmanager\eligibility.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager;

defined('MOODLE_INTERNAL') || die();

/**
 * @group local_quizextensionmanager
 * @covers \local_quizextensionmanager\eligibility
 */
final class eligibility_test extends \advanced_testcase {

    /**
     * Create a course + quiz + enrolled student for use across tests.
     *
     * @param array $quizoptions extra options passed to the quiz generator.
     * @param array $courseoptions extra options passed to the course generator.
     * @return array [\stdClass $course, \stdClass $quiz, \stdClass $student]
     */
    protected function create_fixture(array $quizoptions = [], array $courseoptions = []): array {
        $course = $this->getDataGenerator()->create_course($courseoptions);
        $quiz = $this->getDataGenerator()->create_module('quiz', array_merge([
            'course' => $course->id,
            'timeopen' => 0,
            'timeclose' => 0,
            'timelimit' => 0,
            'attempts' => 0,
        ], $quizoptions));
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        return [$course, $quiz, $student];
    }

    public function test_allows_when_no_restrictions_apply(): void {
        $this->resetAfterTest();
        [, $quiz, $student] = $this->create_fixture();

        $result = eligibility::can_request($quiz->id, $student->id);

        $this->assertTrue($result->allowed);
    }

    public function test_rejects_quiz_not_open_yet(): void {
        $this->resetAfterTest();
        [, $quiz, $student] = $this->create_fixture(['timeopen' => time() + DAYSECS]);

        $result = eligibility::can_request($quiz->id, $student->id);

        $this->assertFalse($result->allowed);
        $this->assertSame('eligibility:notopenyet', $result->reasoncode);
    }

    public function test_rejects_when_request_window_has_closed(): void {
        $this->resetAfterTest();
        global $DB;

        [, $quiz, $student] = $this->create_fixture(['timeclose' => time() - (10 * DAYSECS)]);

        $DB->insert_record('quizextensionmanager_quizset', (object) [
            'quizid' => $quiz->id,
            'enabled' => 1,
            'requirereason' => 0,
            'documentationmode' => 'none',
            'allowedfiletypes' => '',
            'requestwindowtype' => 'days-after-close',
            'requestwindowdays' => 1,
            'requestwindowdate' => null,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $result = eligibility::can_request($quiz->id, $student->id);

        $this->assertFalse($result->allowed);
        $this->assertSame('eligibility:windowclosed', $result->reasoncode);
    }

    public function test_rejects_disabled_quiz(): void {
        $this->resetAfterTest();
        global $DB;

        [, $quiz, $student] = $this->create_fixture();

        $DB->insert_record('quizextensionmanager_quizset', (object) [
            'quizid' => $quiz->id,
            'enabled' => 0,
            'requirereason' => 0,
            'documentationmode' => 'none',
            'allowedfiletypes' => '',
            'requestwindowtype' => 'days-after-close',
            'requestwindowdays' => null,
            'requestwindowdate' => null,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $result = eligibility::can_request($quiz->id, $student->id);

        $this->assertFalse($result->allowed);
        $this->assertSame('eligibility:disabled', $result->reasoncode);
    }

    public function test_rejects_duplicate_pending_request(): void {
        $this->resetAfterTest();
        global $DB;

        [$course, $quiz, $student] = $this->create_fixture();

        $DB->insert_record('quizextensionmanager_request', (object) [
            'quizid' => $quiz->id,
            'courseid' => $course->id,
            'userid' => $student->id,
            'requestedtimeclose' => time() + DAYSECS,
            'requestedtimelimit' => null,
            'requestedattempts' => null,
            'currenttimeclose' => $quiz->timeclose,
            'currenttimelimit' => $quiz->timelimit,
            'currentattempts' => $quiz->attempts,
            'reason' => 'Illness',
            'status' => 'pending',
            'grantedtimeclose' => null,
            'grantedtimelimit' => null,
            'grantedattempts' => null,
            'quizoverrideid' => null,
            'reviewerid' => null,
            'reviewreason' => null,
            'timecreated' => time(),
            'timereviewed' => null,
            'timemodified' => time(),
        ]);

        $result = eligibility::can_request($quiz->id, $student->id);

        $this->assertFalse($result->allowed);
        $this->assertSame('eligibility:pendingexists', $result->reasoncode);
    }

    public function test_does_not_block_on_prior_non_pending_request(): void {
        $this->resetAfterTest();
        global $DB;

        [$course, $quiz, $student] = $this->create_fixture();

        $DB->insert_record('quizextensionmanager_request', (object) [
            'quizid' => $quiz->id,
            'courseid' => $course->id,
            'userid' => $student->id,
            'requestedtimeclose' => time() + DAYSECS,
            'requestedtimelimit' => null,
            'requestedattempts' => null,
            'currenttimeclose' => $quiz->timeclose,
            'currenttimelimit' => $quiz->timelimit,
            'currentattempts' => $quiz->attempts,
            'reason' => 'Illness',
            'status' => 'denied',
            'grantedtimeclose' => null,
            'grantedtimelimit' => null,
            'grantedattempts' => null,
            'quizoverrideid' => null,
            'reviewerid' => null,
            'reviewreason' => 'No.',
            'timecreated' => time(),
            'timereviewed' => time(),
            'timemodified' => time(),
        ]);

        $result = eligibility::can_request($quiz->id, $student->id);

        $this->assertTrue($result->allowed);
    }

    public function test_rejects_when_quota_exceeded_counting_approved_only(): void {
        $this->resetAfterTest();
        global $DB;

        [$course, $quiz, $student] = $this->create_fixture();

        $DB->insert_record('quizextensionmanager_crsset', (object) [
            'courseid' => $course->id,
            'maxrequestsperstudent' => 1,
            'defaultrequestwindowdays' => null,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // A denied request should NOT count against quota.
        $DB->insert_record('quizextensionmanager_request', (object) [
            'quizid' => $quiz->id,
            'courseid' => $course->id,
            'userid' => $student->id,
            'requestedtimeclose' => null,
            'requestedtimelimit' => null,
            'requestedattempts' => null,
            'currenttimeclose' => 0,
            'currenttimelimit' => 0,
            'currentattempts' => 0,
            'reason' => '',
            'status' => 'denied',
            'grantedtimeclose' => null,
            'grantedtimelimit' => null,
            'grantedattempts' => null,
            'quizoverrideid' => null,
            'reviewerid' => null,
            'reviewreason' => '',
            'timecreated' => time(),
            'timereviewed' => time(),
            'timemodified' => time(),
        ]);

        $resultafterdenied = eligibility::can_request($quiz->id, $student->id);
        $this->assertTrue($resultafterdenied->allowed);

        // One approved request should exhaust a quota of 1.
        $DB->insert_record('quizextensionmanager_request', (object) [
            'quizid' => $quiz->id,
            'courseid' => $course->id,
            'userid' => $student->id,
            'requestedtimeclose' => null,
            'requestedtimelimit' => null,
            'requestedattempts' => null,
            'currenttimeclose' => 0,
            'currenttimelimit' => 0,
            'currentattempts' => 0,
            'reason' => '',
            'status' => 'approved',
            'grantedtimeclose' => time() + DAYSECS,
            'grantedtimelimit' => null,
            'grantedattempts' => null,
            'quizoverrideid' => null,
            'reviewerid' => null,
            'reviewreason' => '',
            'timecreated' => time(),
            'timereviewed' => time(),
            'timemodified' => time(),
        ]);

        $resultafterapproved = eligibility::can_request($quiz->id, $student->id);
        $this->assertFalse($resultafterapproved->allowed);
        $this->assertSame('eligibility:quotaexceeded', $resultafterapproved->reasoncode);
    }
}
