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
 * Tests for \local_quizextensionmanager\notification_manager, exercised via
 * request_manager so the coverage matches the real workflow end to end.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager;

defined('MOODLE_INTERNAL') || die();

/**
 * @group local_quizextensionmanager
 * @covers \local_quizextensionmanager\notification_manager
 */
final class notification_manager_test extends \advanced_testcase {

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

    public function test_creating_a_request_notifies_the_teacher(): void {
        $this->resetAfterTest();

        [, $quiz, $student, $teacher] = $this->create_fixture();

        $sink = $this->redirectMessages();

        request_manager::create_request($quiz->id, $student->id, [
            'requestedtimeclose' => $quiz->timeclose + DAYSECS,
            'reason' => 'Medical',
        ]);

        $messages = $sink->get_messages_by_component_and_type('local_quizextensionmanager', 'newrequest');
        $this->assertCount(1, $messages);

        $message = reset($messages);
        $this->assertEquals($teacher->id, $message->useridto);
        $this->assertStringContainsString($quiz->name, $message->subject);
    }

    public function test_creating_a_request_does_not_notify_the_student(): void {
        $this->resetAfterTest();

        [, $quiz, $student] = $this->create_fixture();

        $sink = $this->redirectMessages();

        request_manager::create_request($quiz->id, $student->id, [
            'requestedtimeclose' => $quiz->timeclose + DAYSECS,
            'reason' => 'Medical',
        ]);

        $messages = $sink->get_messages_by_component_and_type('local_quizextensionmanager', 'newrequest');
        foreach ($messages as $message) {
            $this->assertNotEquals($student->id, $message->useridto);
        }
    }

    public function test_approving_a_request_does_not_send_a_duplicate_new_request_notification(): void {
        $this->resetAfterTest();

        [, $quiz, $student, $teacher] = $this->create_fixture();

        $record = request_manager::create_request($quiz->id, $student->id, [
            'requestedtimeclose' => $quiz->timeclose + DAYSECS,
            'reason' => 'Medical',
        ]);

        $sink = $this->redirectMessages();
        request_manager::approve_request($record->id, $teacher->id, [], '');

        $newrequestmessages = $sink->get_messages_by_component_and_type('local_quizextensionmanager', 'newrequest');
        $this->assertCount(0, $newrequestmessages);

        $approvedmessages = $sink->get_messages_by_component_and_type('local_quizextensionmanager', 'extensionapproved');
        $this->assertCount(1, $approvedmessages);
    }
}
