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
 * Tests for \local_quizextensionmanager\override_manager, exercised via
 * request_manager::approve_request() so the coverage matches the real
 * approval flow end to end.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager;

defined('MOODLE_INTERNAL') || die();

/**
 * @group local_quizextensionmanager
 * @covers \local_quizextensionmanager\override_manager
 */
final class override_manager_test extends \advanced_testcase {

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

    public function test_approval_creates_override_with_only_deviating_fields_set(): void {
        $this->resetAfterTest();
        global $DB;

        [, $quiz, $student, $teacher] = $this->create_fixture();

        $newclose = $quiz->timeclose + DAYSECS;

        // Time limit and attempts are not requested (null = no change), so
        // the resulting override should only carry a timeclose value.
        $record = request_manager::create_request($quiz->id, $student->id, [
            'requestedtimeclose' => $newclose,
            'requestedtimelimit' => null,
            'requestedattempts' => null,
            'reason' => 'Medical',
        ]);

        $approved = request_manager::approve_request($record->id, $teacher->id, [], 'Approved');

        $override = $DB->get_record('quiz_overrides', ['quiz' => $quiz->id, 'userid' => $student->id]);

        $this->assertNotFalse($override);
        $this->assertEquals($newclose, (int) $override->timeclose);
        $this->assertNull($override->timelimit);
        $this->assertNull($override->attempts);
        $this->assertEquals($override->id, $approved->quizoverrideid);
    }

    public function test_field_matching_base_quiz_value_is_not_overridden(): void {
        $this->resetAfterTest();
        global $DB;

        [, $quiz, $student, $teacher] = $this->create_fixture();

        // Grant the same attempts value the quiz already has -- should not
        // be written into the override since it does not deviate.
        $record = request_manager::create_request($quiz->id, $student->id, [
            'requestedtimeclose' => $quiz->timeclose + DAYSECS,
            'requestedattempts' => $quiz->attempts,
        ]);

        request_manager::approve_request($record->id, $teacher->id, [], '');

        $override = $DB->get_record('quiz_overrides', ['quiz' => $quiz->id, 'userid' => $student->id]);

        $this->assertNotFalse($override);
        $this->assertNull($override->attempts);
    }

    public function test_second_approval_updates_existing_override_rather_than_duplicating(): void {
        $this->resetAfterTest();
        global $DB;

        [, $quiz, $student, $teacher] = $this->create_fixture();

        $firstclose = $quiz->timeclose + DAYSECS;
        $record1 = request_manager::create_request($quiz->id, $student->id, [
            'requestedtimeclose' => $firstclose,
        ]);
        $approved1 = request_manager::approve_request($record1->id, $teacher->id, [], '');

        $countafterfirst = $DB->count_records('quiz_overrides', ['quiz' => $quiz->id, 'userid' => $student->id]);
        $this->assertSame(1, $countafterfirst);

        // A second, later request for the same student+quiz (allowed since
        // the first is approved, not pending) with different granted values.
        $secondclose = $quiz->timeclose + (3 * DAYSECS);
        $record2 = request_manager::create_request($quiz->id, $student->id, [
            'requestedtimeclose' => $secondclose,
            'requestedattempts' => 1,
        ]);
        $approved2 = request_manager::approve_request($record2->id, $teacher->id, [], '');

        $countaftersecond = $DB->count_records('quiz_overrides', ['quiz' => $quiz->id, 'userid' => $student->id]);
        $this->assertSame(1, $countaftersecond);
        $this->assertEquals($approved1->quizoverrideid, $approved2->quizoverrideid);

        $override = $DB->get_record('quiz_overrides', ['id' => $approved2->quizoverrideid]);
        $this->assertEquals($secondclose, (int) $override->timeclose);
        $this->assertEquals(1, (int) $override->attempts);
    }
}
