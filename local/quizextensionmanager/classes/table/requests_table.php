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
 * Bulk course-wide extension request report table.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * A table_sql subclass listing all extension requests across a course,
 * optionally filtered by status, for the course-level report page.
 */
class requests_table extends \table_sql {

    /**
     * Constructor.
     *
     * @param string $uniqueid unique id for this table.
     * @param int $courseid the course to report on.
     * @param string $status optional status filter (pending/approved/denied/cancelled), '' for all.
     */
    public function __construct(string $uniqueid, int $courseid, string $status = '') {
        parent::__construct($uniqueid);

        $columns = [
            'quizname', 'studentname', 'status', 'requestedtimeclose', 'grantedtimeclose', 'timecreated', 'reviewer',
            'reviewreason', 'actions',
        ];
        $headers = [
            get_string('table:quiz', 'local_quizextensionmanager'),
            get_string('table:student', 'local_quizextensionmanager'),
            get_string('table:status', 'local_quizextensionmanager'),
            get_string('table:requestedtimeclose', 'local_quizextensionmanager'),
            get_string('table:grantedtimeclose', 'local_quizextensionmanager'),
            get_string('table:timecreated', 'local_quizextensionmanager'),
            get_string('table:reviewer', 'local_quizextensionmanager'),
            get_string('table:reviewreason', 'local_quizextensionmanager'),
            get_string('table:actions', 'local_quizextensionmanager'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->collapsible(false);
        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->no_sorting('actions');

        $fields = 'r.id, r.quizid, r.userid, r.status, r.requestedtimeclose, r.grantedtimeclose, ' .
            'r.timecreated, r.reviewerid, r.reviewreason, q.name AS quizname, ' .
            'u.firstname AS ufirstname, u.lastname AS ulastname, ' .
            'rv.firstname AS rvfirstname, rv.lastname AS rvlastname';

        $from = '{quizextensionmanager_request} r ' .
            'JOIN {quiz} q ON q.id = r.quizid ' .
            'JOIN {user} u ON u.id = r.userid ' .
            'LEFT JOIN {user} rv ON rv.id = r.reviewerid';

        $params = ['courseid' => $courseid];
        $where = 'r.courseid = :courseid';
        if ($status !== '') {
            $where .= ' AND r.status = :status';
            $params['status'] = $status;
        }

        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql("SELECT COUNT(1) FROM $from WHERE $where", $params);
    }

    /**
     * Render the student column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_studentname($row) {
        return fullname((object) ['firstname' => $row->ufirstname, 'lastname' => $row->ulastname]);
    }

    /**
     * Render the reviewer column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_reviewer($row) {
        if (empty($row->reviewerid)) {
            return '-';
        }
        return fullname((object) ['firstname' => $row->rvfirstname, 'lastname' => $row->rvlastname]);
    }

    /**
     * Render the reviewer comment column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_reviewreason($row) {
        return !empty($row->reviewreason) ? format_text((string) $row->reviewreason, FORMAT_PLAIN) : '-';
    }

    /**
     * Render the status column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_status($row) {
        return get_string('status:' . $row->status, 'local_quizextensionmanager');
    }

    /**
     * Render the requested close date column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_requestedtimeclose($row) {
        return !empty($row->requestedtimeclose) ? userdate($row->requestedtimeclose) : '-';
    }

    /**
     * Render the granted close date column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_grantedtimeclose($row) {
        return !empty($row->grantedtimeclose) ? userdate($row->grantedtimeclose) : '-';
    }

    /**
     * Render the created-time column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_timecreated($row) {
        return userdate($row->timecreated);
    }

    /**
     * Render the actions column.
     *
     * Only pending requests are actionable, and approving/denying happens
     * in a modal on the per-quiz dashboard (see manage.php and
     * PLUGIN_SPEC.md v0.4), so this links there rather than to a per-request
     * detail view.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_actions($row) {
        if ($row->status !== 'pending') {
            return '-';
        }

        $cm = get_coursemodule_from_instance('quiz', $row->quizid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return '';
        }

        $url = new \moodle_url('/local/quizextensionmanager/manage.php', ['cmid' => $cm->id]);
        return \html_writer::link($url, get_string('table:view', 'local_quizextensionmanager'));
    }
}
