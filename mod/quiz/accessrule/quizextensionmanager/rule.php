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
 * Quiz access rule that places the "Request extension" entry point on the
 * quiz view page. This rule never prevents or restricts attempts -- it only
 * renders UI, via description(), and delegates all actual logic to
 * local_quizextensionmanager.
 *
 * @package    quizaccess_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_quiz\local\access_rule_base;
use mod_quiz\quiz_settings;

/**
 * A rule implementing the "Request extension" entry point.
 *
 * Deliberately does NOT override prevent_access() or any other gating
 * method -- this subplugin must never restrict quiz attempts, it only adds
 * server-rendered content to mod/quiz/view.php via description().
 *
 * Extends \mod_quiz\local\access_rule_base -- confirmed against the real
 * Moodle 5.2.2 core source (the base class was renamed/namespaced from the
 * older global quiz_access_rule_base at some point before 5.2).
 */
class quizaccess_quizextensionmanager extends access_rule_base {

    /**
     * Return an instance of this rule for the given quiz, or null if this
     * rule does not apply.
     *
     * This rule always applies (it only renders an optional UI link), so it
     * unconditionally returns an instance.
     *
     * @param quiz_settings $quizobj information about the quiz in question.
     * @param int $timenow the time that should be considered as "now".
     * @param bool $canignoretimelimits whether the current user is exempt from
     *      time limits and always sees exact times.
     * @return access_rule_base|null the rule, or null if it does not apply.
     */
    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits) {
        return new self($quizobj, $timenow);
    }

    /**
     * Render the "Request extension" entry point shown on the quiz view
     * page, before an attempt is started.
     *
     * All actual eligibility logic (capability checks, per-quiz settings,
     * request window, existing requests, etc.) and the form/link markup
     * itself are owned by local_quizextensionmanager -- this method only
     * delegates to it.
     *
     * TODO: once local_quizextensionmanager\output\extension_link::render()
     * is fully implemented, verify the returned markup/URL matches the final
     * UX (e.g. inline link vs. modal vs. separate page).
     *
     * @return string HTML to display, or an empty string to display nothing.
     */
    public function description() {
        if (!class_exists(\local_quizextensionmanager\output\extension_link::class)) {
            // Defensive: local_quizextensionmanager should always be present
            // (declared as a hard dependency in version.php).
            return '';
        }

        return \local_quizextensionmanager\output\extension_link::render(
            $this->quizobj->get_quizid(),
            $this->quizobj->get_courseid()
        );
    }
}
