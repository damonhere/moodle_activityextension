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
 * Tests for \local_quizextensionmanager\request_manager.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager;

defined('MOODLE_INTERNAL') || die();

/**
 * @group local_quizextensionmanager
 * @covers \local_quizextensionmanager\request_manager
 */
final class request_manager_test extends \advanced_testcase {

    /**
     * Create a course + quiz + enrolled student/teacher for use across tests.
     *
     * @return array [\stdClass $course, \stdClass $quiz, \stdClass $student, \stdClass $teacher]
     */
    protected function create_fixture(): array {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', [
            'course' => $course->id,
            'timeopen' => 0,
            'timeclose' => time() + (7 * DAYSECS),
            'timelimit' => 3600,
            'attempts' => 2,
        ]);
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        return [$course, $quiz, $student, $teacher];
    }

    public function test_create_request_snapshots_current_quiz_values(): void {
        $this->resetAfterTest();
        [, $quiz, $student] = $this->create_fixture();

        $newclose = $quiz->timeclose + DAYSECS;
        $record = request_manager::create_request($quiz->id, $student->id, [
            'requestedtimeclose' => $newclose,
            'requestedtimelimit' => null,
            'requestedattempts' => null,
            'reason' => 'Family emergency',
        ]);

        $this->assertSame('pending', $record->status);
        $this->assertEquals($quiz->timeclose, $record->currenttimeclose);
        $this->assertEquals($quiz->timelimit, $record->currenttimelimit);
        $this->assertEquals($quiz->attempts, $record->currentattempts);
        $this->assertEquals($newclose, $record->requestedtimeclose);
        $this->assertNull($record->requestedtimelimit);
        $this->assertNull($record->requestedattempts);
    }

    public function test_create_request_rejects_when_ineligible(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', [
            'course' => $course->id,
            'timeopen' => time() + DAYSECS,
        ]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->expectException(\moodle_exception::class);
        request_manager::create_request($quiz->id, $student->id, ['requestedtimelimit' => 60]);
    }

    public function test_cancel_request_sets_status_without_deleting_row(): void {
        $this->resetAfterTest();
        global $DB;

        [, $quiz, $student] = $this->create_fixture();

        $record = request_manager::create_request($quiz->id, $student->id, [
            'requestedtimeclose' => $quiz->timeclose + DAYSECS,
        ]);

        request_manager::cancel_request($record->id, $student->id);

        $reloaded = $DB->get_record('quizextensionmanager_request', ['id' => $record->id]);
        $this->assertNotFalse($reloaded);
        $this->assertSame('cancelled', $reloaded->status);
    }

    public function test_cancel_request_rejects_non_owner(): void {
        $this->resetAfterTest();
        [, $quiz, $student, $teacher] = $this->create_fixture();

        $record = request_manager::create_request($quiz->id, $student->id, [
            'requestedtimeclose' => $quiz->timeclose + DAYSECS,
        ]);

        $this->expectException(\moodle_exception::class);
        request_manager::cancel_request($record->id, $teacher->id);
    }

    public function test_update_request_rejected_once_no_longer_pending(): void {
        $this->resetAfterTest();
        [, $quiz, $student] = $this->create_fixture();

        $record = request_manager::create_request($quiz->id, $student->id, [
            'requestedtimeclose' => $quiz->timeclose + DAYSECS,
        ]);
        request_manager::cancel_request($record->id, $student->id);

        $this->expectException(\moodle_exception::class);
        request_manager::update_request($record->id, $student->id, ['reason' => 'Changed my mind']);
    }

    public function test_quota_counts_approved_status_only(): void {
        $this->resetAfterTest();
        [$course, $quiz, $student, $teacher] = $this->create_fixture();

        $approved = request_manager::create_request($quiz->id, $student->id, [
            'requestedtimeclose' => $quiz->timeclose + DAYSECS,
        ]);
        request_manager::approve_request($approved->id, $teacher->id, [], 'ok');

        $denied = request_manager::create_request($quiz->id, $student->id, [
            'requestedtimeclose' => $quiz->timeclose + (2 * DAYSECS),
        ]);
        request_manager::deny_request($denied->id, $teacher->id, 'no');

        [$used, $max] = request_manager::get_quota($course->id, $student->id);

        $this->assertSame(1, $used);
        $this->assertNull($max);
    }

    public function test_approve_request_defaults_granted_to_requested(): void {
        $this->resetAfterTest();
        [, $quiz, $student, $teacher] = $this->create_fixture();

        $newclose = $quiz->timeclose + DAYSECS;
        $record = request_manager::create_request($quiz->id, $student->id, [
            'requestedtimeclose' => $newclose,
            'requestedattempts' => 3,
        ]);

        $approved = request_manager::approve_request($record->id, $teacher->id, [], 'Approved');

        $this->assertSame('approved', $approved->status);
        $this->assertEquals($newclose, $approved->grantedtimeclose);
        $this->assertEquals(3, $approved->grantedattempts);
        $this->assertNull($approved->grantedtimelimit);
        $this->assertEquals($teacher->id, $approved->reviewerid);
    }
}
