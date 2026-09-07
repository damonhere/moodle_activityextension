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
 * Applies an approved extension request to a mod_quiz user override, by
 * delegating to mod_quiz's own \mod_quiz\local\override_manager (confirmed
 * against the real Moodle 5.2.2 core source), rather than writing to
 * {quiz_overrides} directly.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager;

defined('MOODLE_INTERNAL') || die();

/**
 * Creates/updates the single {quiz_overrides} row for a student on a quiz to
 * reflect an approved request's granted values, via mod_quiz's own override
 * manager so that cache purging, event triggering, and calendar/in-progress
 * attempt refresh all happen exactly as they would from the native "Add
 * user override" screen.
 */
class override_manager {

    /**
     * Apply an approved request's granted values to a mod_quiz user override.
     *
     * Deliberately does not call mod_quiz's own
     * \mod_quiz\local\override_manager::require_manage_capability(), which
     * checks mod/quiz:manageoverrides -- this plugin gates approval on its
     * own local/quizextensionmanager:manage capability instead (per the
     * spec, either capability is an acceptable gate), so no capability
     * re-check happens here; the caller (request_manager::approve_request(),
     * invoked only from pages that already require that capability) is
     * responsible for authorization.
     *
     * @param \stdClass $request the (already-approved) request record, with granted* fields set.
     * @return int|null the quiz_overrides.id that was created/updated, or null if there was nothing
     *      to override (every granted field matched the quiz's own setting and no override exists yet)
     *      or the quiz no longer exists.
     */
    public static function apply_override(\stdClass $request): ?int {
        global $DB;

        $quiz = $DB->get_record('quiz', ['id' => $request->quizid]);
        if (!$quiz) {
            return null;
        }

        try {
            $quizsettings = \mod_quiz\quiz_settings::create((int) $request->quizid);
        } catch (\Throwable $e) {
            debugging(
                'local_quizextensionmanager: could not load quiz_settings for quiz ' . $request->quizid
                    . ': ' . $e->getMessage(),
                DEBUG_DEVELOPER
            );
            return null;
        }
        $manager = $quizsettings->get_override_manager();

        $formdata = [
            'quiz' => $request->quizid,
            'userid' => $request->userid,
            // Surfaced in Moodle's own "quiz overrides" listing so a teacher
            // browsing there (outside this plugin) can see why the override
            // exists; also satisfies save_override()'s validation on an
            // update when no granted field actually deviates this round.
            'reason' => get_string('override:reason', 'local_quizextensionmanager', $request->id),
        ];

        $haschange = false;
        foreach (['timeclose', 'timelimit', 'attempts'] as $field) {
            $granted = $request->{'granted' . $field};
            if ($granted !== null && $granted !== '') {
                $formdata[$field] = (int) $granted;
                if ((int) $granted !== (int) $quiz->$field) {
                    $haschange = true;
                }
            }
        }

        $existing = self::find_existing_override($request);
        if ($existing) {
            $formdata['id'] = $existing->id;
            // save_override() resets every OVERRIDEABLE_QUIZ_SETTINGS field
            // we don't pass back to the quiz default (null). Carry forward
            // any timeopen/password this override already has (e.g. set by
            // a teacher via Moodle's native override screen, unrelated to
            // this plugin) so approving a request never silently clobbers
            // them.
            $formdata['timeopen'] = $existing->timeopen;
            $formdata['password'] = $existing->password;
        } else if (!$haschange) {
            // Nothing actually deviates from the quiz's own settings and
            // there is no existing override to attach a reason to.
            // save_override() would reject a bare create with no override
            // data, and there is genuinely nothing to override.
            return null;
        }

        try {
            return $manager->save_override($formdata);
        } catch (\Throwable $e) {
            debugging(
                'local_quizextensionmanager: save_override() failed for request ' . $request->id
                    . ': ' . $e->getMessage(),
                DEBUG_DEVELOPER
            );
            return null;
        }
    }

    /**
     * Find any existing user-override row for this request's quiz+user.
     *
     * Moodle only allows one user-override row per user per quiz (enforced
     * by mod_quiz's own override_manager::validate_data()), so this is
     * needed to pass the existing row's id back into save_override() as an
     * update rather than a rejected duplicate create.
     *
     * @param \stdClass $request the request record.
     * @return \stdClass|false the existing quiz_overrides row, or false if none.
     */
    protected static function find_existing_override(\stdClass $request) {
        global $DB;

        if (!empty($request->quizoverrideid)) {
            $existing = $DB->get_record('quiz_overrides', ['id' => $request->quizoverrideid]);
            if ($existing) {
                return $existing;
            }
        }

        // User overrides have groupid IS NULL (a group override would have
        // userid IS NULL instead), so this can't be expressed as a simple
        // get_record() equality match.
        return $DB->get_record_select(
            'quiz_overrides',
            'quiz = :quiz AND userid = :userid AND groupid IS NULL',
            ['quiz' => $request->quizid, 'userid' => $request->userid]
        );
    }
}
