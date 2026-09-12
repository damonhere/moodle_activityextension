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
 * Smoke test for local_quizextensionmanager.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager;

defined('MOODLE_INTERNAL') || die();

/**
 * @group local_quizextensionmanager
 * @covers \local_quizextensionmanager\privacy\provider
 */
final class smoke_test extends \advanced_testcase {

    /**
     * The plugin should install cleanly and its privacy provider should be autoloadable.
     */
    public function test_plugin_loads(): void {
        $this->resetAfterTest();
        $this->assertTrue(class_exists(\local_quizextensionmanager\privacy\provider::class));
    }
}
