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
 * Library functions and callbacks for local_quizextensionmanager.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Serve files from the local_quizextensionmanager file areas.
 *
 * Documentation uploaded by a student in support of a request is stored in
 * the 'documentation' filearea, keyed by the request's id as itemid. Access
 * is restricted to the request's own owner or a manager for that request's
 * quiz.
 *
 * @param stdClass $course course object.
 * @param stdClass|null $cm course module object, if the file was requested in a module context.
 * @param context $context context object.
 * @param string $filearea file area.
 * @param array $args extra arguments.
 * @param bool $forcedownload whether or not force download.
 * @param array $options additional options affecting the file serving.
 * @return bool false if the file was not found or the user does not have permission, does not return if successful.
 */
function local_quizextensionmanager_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB, $USER;

    require_login($course, false, $cm);

    if ($filearea !== 'documentation') {
        return false;
    }

    $itemid = (int) array_shift($args);

    $requestrecord = $DB->get_record('quizextensionmanager_request', ['id' => $itemid]);
    if (!$requestrecord) {
        return false;
    }

    // If we were called in a module context, make sure it actually matches
    // the quiz this request belongs to (defence against a mismatched itemid).
    if ($cm && (int) $requestrecord->quizid !== (int) $cm->instance) {
        return false;
    }

    $isowner = ((int) $requestrecord->userid === (int) $USER->id);
    $ismanager = has_capability('local/quizextensionmanager:manage', $context);

    if (!$isowner && !$ismanager) {
        return false;
    }

    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_quizextensionmanager', $filearea, $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Extend the settings navigation to add entry points for managing extension
 * requests and settings.
 *
 * Adds "Extension requests" and "Extension request settings" links under a
 * quiz module context (capability-gated on local/quizextensionmanager:manage),
 * and "Extension request report" and "Extension request course settings"
 * links under a course context (same capability, checked at course level as
 * a coarse gate).
 *
 * @param settings_navigation $settingsnav the settings navigation object.
 * @param context $context the context of the page being viewed.
 */
function local_quizextensionmanager_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    if ($context instanceof context_module) {
        $cm = get_coursemodule_from_id('', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm || $cm->modname !== 'quiz') {
            return;
        }

        if (!has_capability('local/quizextensionmanager:manage', $context)) {
            return;
        }

        $node = $settingsnav->find('modulesettings', navigation_node::TYPE_SETTING);
        if (!$node) {
            return;
        }

        $node->add(
            get_string('page:manage', 'local_quizextensionmanager'),
            new moodle_url('/local/quizextensionmanager/manage.php', ['cmid' => $cm->id]),
            navigation_node::TYPE_SETTING,
            null,
            'quizextensionmanagermanage'
        );
        $node->add(
            get_string('page:quizsettings', 'local_quizextensionmanager'),
            new moodle_url('/local/quizextensionmanager/quizsettings.php', ['cmid' => $cm->id]),
            navigation_node::TYPE_SETTING,
            null,
            'quizextensionmanagerquizsettings'
        );
    } else if ($context instanceof context_course) {
        if ($context->instanceid == SITEID) {
            return;
        }

        if (!has_capability('local/quizextensionmanager:manage', $context)) {
            return;
        }

        $node = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE);
        if (!$node) {
            return;
        }

        $node->add(
            get_string('page:report', 'local_quizextensionmanager'),
            new moodle_url('/local/quizextensionmanager/report.php', ['courseid' => $context->instanceid]),
            navigation_node::TYPE_SETTING,
            null,
            'quizextensionmanagerreport'
        );
        $node->add(
            get_string('page:coursesettings', 'local_quizextensionmanager'),
            new moodle_url('/local/quizextensionmanager/coursesettings.php', ['courseid' => $context->instanceid]),
            navigation_node::TYPE_SETTING,
            null,
            'quizextensionmanagercoursesettings'
        );
    }
}
