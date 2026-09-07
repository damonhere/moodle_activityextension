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
 * Privacy provider for local_quizextensionmanager.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider. This plugin stores personal data: extension requests
 * (including free-text reasons) and student-uploaded documentation files.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /** @var string Name of the main request table. */
    const REQUEST_TABLE = 'quizextensionmanager_request';

    /**
     * Describe the personal data stored by this plugin.
     *
     * @param collection $collection the metadata collection to add to.
     * @return collection the updated metadata collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(self::REQUEST_TABLE, [
            'quizid'             => 'privacy:metadata:quizextensionmanager_request:quizid',
            'courseid'           => 'privacy:metadata:quizextensionmanager_request:courseid',
            'userid'             => 'privacy:metadata:quizextensionmanager_request:userid',
            'requestedtimeclose' => 'privacy:metadata:quizextensionmanager_request:requestedtimeclose',
            'requestedtimelimit' => 'privacy:metadata:quizextensionmanager_request:requestedtimelimit',
            'requestedattempts'  => 'privacy:metadata:quizextensionmanager_request:requestedattempts',
            'currenttimeclose'   => 'privacy:metadata:quizextensionmanager_request:currenttimeclose',
            'currenttimelimit'   => 'privacy:metadata:quizextensionmanager_request:currenttimelimit',
            'currentattempts'    => 'privacy:metadata:quizextensionmanager_request:currentattempts',
            'reason'             => 'privacy:metadata:quizextensionmanager_request:reason',
            'status'             => 'privacy:metadata:quizextensionmanager_request:status',
            'grantedtimeclose'   => 'privacy:metadata:quizextensionmanager_request:grantedtimeclose',
            'grantedtimelimit'   => 'privacy:metadata:quizextensionmanager_request:grantedtimelimit',
            'grantedattempts'    => 'privacy:metadata:quizextensionmanager_request:grantedattempts',
            'quizoverrideid'     => 'privacy:metadata:quizextensionmanager_request:quizoverrideid',
            'reviewerid'         => 'privacy:metadata:quizextensionmanager_request:reviewerid',
            'reviewreason'       => 'privacy:metadata:quizextensionmanager_request:reviewreason',
            'timecreated'        => 'privacy:metadata:quizextensionmanager_request:timecreated',
            'timereviewed'       => 'privacy:metadata:quizextensionmanager_request:timereviewed',
            'timemodified'       => 'privacy:metadata:quizextensionmanager_request:timemodified',
        ], 'privacy:metadata:quizextensionmanager_request');

        // Uploaded supporting documentation is stored via the File API (core_files subsystem).
        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:quizextensionmanager_documentation');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the given user.
     *
     * A request "belongs" to the quiz module context of the quiz it was made
     * against, whether the given user was the requester or the reviewer.
     *
     * @param int $userid the user to search.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {" . self::REQUEST_TABLE . "} r
                  JOIN {modules} m ON m.name = :modname
                  JOIN {course_modules} cm ON cm.instance = r.quizid AND cm.module = m.id
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextmodule
                 WHERE r.userid = :userid1 OR r.reviewerid = :userid2";

        $contextlist->add_from_sql($sql, [
            'modname'       => 'quiz',
            'contextmodule' => CONTEXT_MODULE,
            'userid1'       => $userid,
            'userid2'       => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist the userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $sql = "SELECT r.userid, r.reviewerid
                  FROM {" . self::REQUEST_TABLE . "} r
                  JOIN {course_modules} cm ON cm.instance = r.quizid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid";
        $params = ['modname' => 'quiz', 'cmid' => $context->instanceid];

        $userlist->add_from_sql('userid', $sql, $params);
        $userlist->add_from_sql('reviewerid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }

            $cm = get_coursemodule_from_id('quiz', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $records = $DB->get_records(self::REQUEST_TABLE, ['quizid' => $cm->instance, 'userid' => $user->id]);
            if (empty($records)) {
                continue;
            }

            $data = (object) [
                'requests' => array_values(array_map(static function ($record) {
                    return [
                        'status'             => $record->status,
                        'reason'             => $record->reason,
                        'requestedtimeclose' => $record->requestedtimeclose
                            ? \core_privacy\local\request\transform::datetime($record->requestedtimeclose) : null,
                        'requestedtimelimit' => $record->requestedtimelimit,
                        'requestedattempts'  => $record->requestedattempts,
                        'grantedtimeclose'   => $record->grantedtimeclose
                            ? \core_privacy\local\request\transform::datetime($record->grantedtimeclose) : null,
                        'grantedtimelimit'   => $record->grantedtimelimit,
                        'grantedattempts'    => $record->grantedattempts,
                        'reviewreason'       => $record->reviewreason,
                        'timecreated'        => \core_privacy\local\request\transform::datetime($record->timecreated),
                        'timemodified'       => \core_privacy\local\request\transform::datetime($record->timemodified),
                    ];
                }, $records)),
            ];

            writer::with_context($context)->export_data([get_string('pluginname', 'local_quizextensionmanager')], $data);

            // Export any documentation files attached to each of this user's requests.
            foreach ($records as $record) {
                writer::with_context($context)->export_area_files(
                    [get_string('pluginname', 'local_quizextensionmanager')],
                    'local_quizextensionmanager',
                    'documentation',
                    $record->id
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('quiz', $context->instanceid);
        if (!$cm) {
            return;
        }

        $requestids = $DB->get_fieldset_select(self::REQUEST_TABLE, 'id', 'quizid = :quizid', ['quizid' => $cm->instance]);

        $fs = get_file_storage();
        foreach ($requestids as $requestid) {
            $fs->delete_area_files($context->id, 'local_quizextensionmanager', 'documentation', $requestid);
        }

        $DB->delete_records(self::REQUEST_TABLE, ['quizid' => $cm->instance]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }

            $cm = get_coursemodule_from_id('quiz', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $requestids = $DB->get_fieldset_select(
                self::REQUEST_TABLE,
                'id',
                'quizid = :quizid AND userid = :userid',
                ['quizid' => $cm->instance, 'userid' => $user->id]
            );

            $fs = get_file_storage();
            foreach ($requestids as $requestid) {
                $fs->delete_area_files($context->id, 'local_quizextensionmanager', 'documentation', $requestid);
            }

            $DB->delete_records(self::REQUEST_TABLE, ['quizid' => $cm->instance, 'userid' => $user->id]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist the approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('quiz', $context->instanceid);
        if (!$cm) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params['quizid'] = $cm->instance;

        $requestids = $DB->get_fieldset_select(
            self::REQUEST_TABLE,
            'id',
            "quizid = :quizid AND userid $insql",
            $params
        );

        $fs = get_file_storage();
        foreach ($requestids as $requestid) {
            $fs->delete_area_files($context->id, 'local_quizextensionmanager', 'documentation', $requestid);
        }

        $DB->delete_records_select(self::REQUEST_TABLE, "quizid = :quizid AND userid $insql", $params);
    }
}
